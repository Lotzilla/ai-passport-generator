"""
api/main.py
────────────
FastAPI server exposing three endpoints:

  POST /detect-face         — face detection + landmarks
  POST /remove-background   — background removal (white or transparent)
  POST /process-image       — full pipeline: detect + remove

All endpoints accept multipart/form-data with an 'image' file field.
"""
from __future__ import annotations

import base64
import sys
import os

# Ensure project root is on sys.path so models/services/utils resolve.
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from fastapi import FastAPI, File, Form, HTTPException, UploadFile
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse, Response

from services.face_service import process_face_image, process_glasses_detection
from services.segmentation_service import process_segmentation

# ── App setup ─────────────────────────────────────────────────────────────────

app = FastAPI(
    title="AI Passport Photo Pipeline",
    description="Face detection + background removal for passport photo generation.",
    version="1.0.0",
)

# Allow the Laravel backend (and local dev) to call this service.
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],    # Tighten in production: ["https://aipassportgenerator.test"]
    allow_methods=["GET", "POST"],
    allow_headers=["*"],
)


# ── Health check ──────────────────────────────────────────────────────────────

@app.get("/health")
def health() -> dict:
    return {"status": "ok", "service": "ai-passport-pipeline"}


# ── POST /detect-face ─────────────────────────────────────────────────────────

@app.post("/detect-face")
async def detect_face(image: UploadFile = File(...)) -> JSONResponse:
    """
    Detect the face bounding box and landmarks in the uploaded image.

    Returns JSON:
    {
      "success": true,
      "faceDetected": true,
      "faceBox": {"x": 120, "y": 45, "width": 210, "height": 260, "img_w": 640, "img_h": 480},
      "landmarks": {
          "leftEye":  [185, 130],
          "rightEye": [275, 128],
          "noseTip":  [230, 175],
          "chin":     [228, 285]
      },
      "confidence": 0.9823
    }
    """
    _validate_image_mime(image)
    image_bytes = await image.read()
    result = process_face_image(image_bytes)

    if not result["success"]:
        raise HTTPException(status_code=422, detail=result.get("error", "Face detection failed"))

    return JSONResponse(content=result)


# ── POST /remove-background ───────────────────────────────────────────────────

@app.post("/remove-background")
async def remove_background(
    image: UploadFile = File(...),
    white_bg: bool    = Form(True),
    return_base64: bool = Form(False),
) -> Response:
    """
    Remove the background from the uploaded image.

    Form fields:
      image        : image file (JPEG / PNG / WebP)
      white_bg     : true  → return RGB on white canvas (default)
                     false → return RGBA with transparent background
      return_base64: true  → return JSON { "image": "<base64>", "meta": {...} }
                     false → return raw PNG bytes (default)

    Returns:
      PNG file (Content-Type: image/png)  — when return_base64 is false
      JSON { "image": "...", "meta": {...} } — when return_base64 is true
    """
    _validate_image_mime(image)
    image_bytes = await image.read()

    png_bytes, meta = process_segmentation(image_bytes, white_background=white_bg)

    if not meta["success"]:
        raise HTTPException(status_code=422, detail=meta.get("error", "Segmentation failed"))

    if return_base64:
        return JSONResponse(content={
            "image": base64.b64encode(png_bytes).decode("utf-8"),
            "meta":  meta,
        })

    return Response(content=png_bytes, media_type="image/png")


# ── POST /process-image ───────────────────────────────────────────────────────

@app.post("/process-image")
async def process_image(
    image: UploadFile = File(...),
    white_bg: bool    = Form(True),
) -> JSONResponse:
    """
    Full pipeline: run face detection AND background removal on one image.

    Returns JSON:
    {
      "success": true,
      "face": {
          "faceDetected": true,
          "faceBox": {...},
          "landmarks": {...},
          "confidence": 0.98
      },
      "processedImage": "<base64-encoded PNG>",
      "meta": {
          "model": "u2net",
          "white_background": true,
          "width": 640,
          "height": 480
      }
    }
    """
    _validate_image_mime(image)
    image_bytes = await image.read()

    # ── Face detection ────────────────────────────────────────────────────
    face_result = process_face_image(image_bytes)
    if not face_result["success"]:
        raise HTTPException(status_code=422, detail=face_result.get("error", "Face detection failed"))

    if not face_result["faceDetected"]:
        raise HTTPException(status_code=422, detail="No face detected in the image.")

    # ── Background removal ────────────────────────────────────────────────
    png_bytes, seg_meta = process_segmentation(image_bytes, white_background=white_bg)
    if not seg_meta["success"]:
        raise HTTPException(status_code=422, detail=seg_meta.get("error", "Segmentation failed"))

    b64_image = base64.b64encode(png_bytes).decode("utf-8")

    return JSONResponse(content={
        "success":        True,
        "face":           {k: v for k, v in face_result.items() if k != "success"},
        "processedImage": b64_image,
        "meta":           seg_meta,
    })


# ── POST /detect-glasses ─────────────────────────────────────────────────────

@app.post("/detect-glasses")
async def detect_glasses_endpoint(image: UploadFile = File(...)) -> JSONResponse:
    """
    Detect whether the person in the photo is wearing glasses.

    Returns JSON:
    {
      "success": true,
      "glasses_detected": true,
      "confidence": 0.73
    }

    Uses MediaPipe eye-region landmarks combined with edge/brightness
    analysis to detect glasses frames and lens reflections.
    """
    _validate_image_mime(image)
    image_bytes = await image.read()
    result = process_glasses_detection(image_bytes)
    return JSONResponse(content=result)


# ── Validation helper ─────────────────────────────────────────────────────────

_ALLOWED_MIME = {"image/jpeg", "image/png", "image/webp", "image/jpg"}

def _validate_image_mime(upload: UploadFile) -> None:
    if upload.content_type and upload.content_type not in _ALLOWED_MIME:
        raise HTTPException(
            status_code=415,
            detail=f"Unsupported image type '{upload.content_type}'. Use JPEG, PNG, or WebP.",
        )


# ── Entry point ───────────────────────────────────────────────────────────────

if __name__ == "__main__":
    import uvicorn
    uvicorn.run("api.main:app", host="0.0.0.0", port=8000, reload=True)
