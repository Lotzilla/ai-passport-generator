"""
services/face_service.py
─────────────────────────
Thin service layer between the API endpoints and the FaceDetector model.
Handles image decoding, error wrapping, and response normalisation.
"""
from __future__ import annotations

from models.face_model import get_detector
from utils.image_processing import bytes_to_numpy


def process_face_image(image_bytes: bytes) -> dict:
    """
    Decode an image and run face detection + landmark extraction.

    Returns the structured dict from FaceDetector.detect(), plus a
    top-level 'success' key and an optional 'error' message.

    Example return value (face found):
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

    """
    try:
        rgb = bytes_to_numpy(image_bytes)
    except ValueError as exc:
        return {"success": False, "error": str(exc), "faceDetected": False}

    try:
        detector = get_detector()
        result   = detector.detect(rgb)
    except Exception as exc:
        return {"success": False, "error": f"Face detection failed: {exc}", "faceDetected": False}

    return {"success": True, **result}


def process_glasses_detection(image_bytes: bytes) -> dict:
    """
    Decode an image, run face detection to get eye landmarks, then
    analyse the eye region to determine whether glasses are present.

    Returns:
        {
            "success": true,
            "glasses_detected": bool,
            "confidence": float  # 0-1
        }
    """
    try:
        rgb = bytes_to_numpy(image_bytes)
    except ValueError as exc:
        return {"success": False, "error": str(exc), "glasses_detected": False, "confidence": 0.0}

    try:
        detector    = get_detector()
        face_result = detector.detect(rgb)

        if not face_result.get("faceDetected"):
            # Can't detect glasses if no face is present
            return {"success": True, "glasses_detected": False, "confidence": 0.0}

        landmarks      = face_result.get("landmarks")
        glasses_result = detector.detect_glasses(rgb, landmarks)

        return {"success": True, **glasses_result}

    except Exception as exc:
        return {
            "success":          False,
            "error":            f"Glasses detection failed: {exc}",
            "glasses_detected": False,
            "confidence":       0.0,
        }
