"""
models/face_model.py
────────────────────
Face detection using MediaPipe.

Two MediaPipe solutions are used together:
  • FaceDetection  — fast bounding-box detector (< 6 ms on CPU)
  • FaceMesh       — 468 3-D landmark predictor

The returned dict matches the JSON contract expected by the Laravel backend:

    {
      "faceDetected": bool,
      "faceBox": {
          "x": int, "y": int,          # top-left corner (pixels)
          "width": int, "height": int,
          "img_w": int, "img_h": int   # original image dimensions
      },
      "landmarks": {
          "leftEye":  [x, y],
          "rightEye": [x, y],
          "noseTip":  [x, y],
          "chin":     [x, y]
      },
      "confidence": float  # 0–1
    }
"""
from __future__ import annotations

import os
import urllib.request
from typing import Optional

import mediapipe as mp
import numpy as np

# ── Model file paths (downloaded once on first run) ──────────────────────────
_MODELS_DIR           = os.path.dirname(os.path.abspath(__file__))
_FACE_DETECTOR_PATH   = os.path.join(_MODELS_DIR, "blaze_face_short_range.tflite")
_FACE_LANDMARKER_PATH = os.path.join(_MODELS_DIR, "face_landmarker.task")

_FACE_DETECTOR_URL = (
    "https://storage.googleapis.com/mediapipe-models/face_detector/"
    "blaze_face_short_range/float16/1/blaze_face_short_range.tflite"
)
_FACE_LANDMARKER_URL = (
    "https://storage.googleapis.com/mediapipe-models/face_landmarker/"
    "face_landmarker/float16/1/face_landmarker.task"
)


def _ensure_model(url: str, path: str) -> None:
    """Download model file if not already cached locally."""
    if not os.path.exists(path):
        os.makedirs(os.path.dirname(path), exist_ok=True)
        urllib.request.urlretrieve(url, path)


# ── MediaPipe landmark indices (canonical 478-point FaceLandmarker model) ─────
_LEFT_EYE_INDICES  = [33, 160, 158, 133, 153, 144]   # left  iris ring
_RIGHT_EYE_INDICES = [362, 385, 387, 263, 373, 380]  # right iris ring
_NOSE_TIP_INDEX    = 4
_CHIN_INDEX        = 152


class FaceDetector:
    """
    Wrapper around MediaPipe FaceDetection + FaceMesh.

    Usage:
        detector = FaceDetector()
        result   = detector.detect(rgb_array)   # numpy (H, W, 3) RGB
    """

    def __init__(self, min_detection_confidence: float = 0.5) -> None:
        _ensure_model(_FACE_DETECTOR_URL,   _FACE_DETECTOR_PATH)
        _ensure_model(_FACE_LANDMARKER_URL, _FACE_LANDMARKER_PATH)

        from mediapipe.tasks.python import BaseOptions
        from mediapipe.tasks.python import vision as mp_vision

        det_opts = mp_vision.FaceDetectorOptions(
            base_options=BaseOptions(model_asset_path=_FACE_DETECTOR_PATH),
            min_detection_confidence=min_detection_confidence,
        )
        self._detector = mp_vision.FaceDetector.create_from_options(det_opts)

        lm_opts = mp_vision.FaceLandmarkerOptions(
            base_options=BaseOptions(model_asset_path=_FACE_LANDMARKER_PATH),
            num_faces=1,
            min_face_detection_confidence=min_detection_confidence,
            min_face_presence_confidence=min_detection_confidence,
        )
        self._landmarker = mp_vision.FaceLandmarker.create_from_options(lm_opts)

    # ── Public API ─────────────────────────────────────────────────────────────

    def detect(self, rgb_image: np.ndarray) -> dict:
        """
        Run face detection + landmark extraction on an RGB numpy array.

        Returns the structured dict described in the module docstring.
        """
        h, w = rgb_image.shape[:2]
        empty = self._empty_result(w, h)

        mp_img = mp.Image(image_format=mp.ImageFormat.SRGB, data=rgb_image)

        # ── Step 1: bounding box ───────────────────────────────────────────
        det_result = self._detector.detect(mp_img)
        if not det_result.detections:
            return empty

        detection  = det_result.detections[0]   # highest-confidence face
        confidence = detection.categories[0].score
        bb         = detection.bounding_box      # pixel coords in Tasks API
        face_box   = {
            "x":      max(0, bb.origin_x),
            "y":      max(0, bb.origin_y),
            "width":  min(bb.width,  w - max(0, bb.origin_x)),
            "height": min(bb.height, h - max(0, bb.origin_y)),
        }

        # ── Step 2: landmarks via FaceLandmarker ──────────────────────────
        lm_result = self._landmarker.detect(mp_img)
        if not lm_result.face_landmarks:
            # Bounding box found but landmarks failed — return box only
            return {
                "faceDetected": True,
                "faceBox":      {**face_box, "img_w": w, "img_h": h},
                "landmarks":    None,
                "confidence":   round(float(confidence), 4),
            }

        lm = lm_result.face_landmarks[0]   # list of NormalizedLandmark

        def px(idx: int) -> list[int]:
            """Landmark index → [pixel_x, pixel_y]."""
            pt = lm[idx]
            return [int(pt.x * w), int(pt.y * h)]

        def centroid(indices: list[int]) -> list[int]:
            xs = [lm[i].x * w for i in indices]
            ys = [lm[i].y * h for i in indices]
            return [int(sum(xs) / len(xs)), int(sum(ys) / len(ys))]

        landmarks = {
            "leftEye":  centroid(_LEFT_EYE_INDICES),
            "rightEye": centroid(_RIGHT_EYE_INDICES),
            "noseTip":  px(_NOSE_TIP_INDEX),
            "chin":     px(_CHIN_INDEX),
        }

        return {
            "faceDetected": True,
            "faceBox":      {**face_box, "img_w": w, "img_h": h},
            "landmarks":    landmarks,
            "confidence":   round(float(confidence), 4),
        }

    def close(self) -> None:
        """Release MediaPipe resources."""
        self._detector.close()
        self._landmarker.close()

    # ── Glasses detection ──────────────────────────────────────────────────────

    def detect_glasses(self, rgb_image: np.ndarray, landmarks_dict: Optional[dict]) -> dict:
        """
        Heuristic glasses detection using the eye-region of the image.

        Analyses the rectangular region spanning both eyes for:
          1. Strong horizontal edges  — glasses frames sit above/below the eye
          2. Bright specular spots    — lens glass creates characteristic reflections
          3. Dark tinted band         — tinted / coloured lenses darken the eye area

        Returns:
            { "glasses_detected": bool, "confidence": float 0-1 }
        """
        import cv2  # imported here to keep the module import lightweight

        h, w = rgb_image.shape[:2]

        if not landmarks_dict:
            return {"glasses_detected": False, "confidence": 0.0}

        left_eye  = landmarks_dict.get("leftEye")
        right_eye = landmarks_dict.get("rightEye")

        if not left_eye or not right_eye:
            return {"glasses_detected": False, "confidence": 0.0}

        ied = max(1, right_eye[0] - left_eye[0])   # inter-eye distance (px)
        ey  = (left_eye[1] + right_eye[1]) // 2

        # ROI that covers both lenses plus a little margin
        x1 = max(0, left_eye[0]  - int(ied * 0.55))
        x2 = min(w, right_eye[0] + int(ied * 0.55))
        y1 = max(0, ey - int(ied * 0.40))
        y2 = min(h, ey + int(ied * 0.35))

        if x2 - x1 < 4 or y2 - y1 < 4:
            return {"glasses_detected": False, "confidence": 0.0}

        roi  = rgb_image[y1:y2, x1:x2]
        gray = cv2.cvtColor(roi, cv2.COLOR_RGB2GRAY)
        rh, rw = gray.shape

        # 1. Horizontal edge density ─ glasses frames create strong horizontal edges
        edges  = cv2.Canny(gray, 30, 90)
        h_kern = cv2.getStructuringElement(cv2.MORPH_RECT, (max(3, rw // 4), 1))
        h_edges     = cv2.morphologyEx(edges, cv2.MORPH_OPEN, h_kern)
        edge_score  = float(np.sum(h_edges > 0)) / max(edges.size, 1)

        # 2. Specular reflections ─ bright spots from lens glass
        lens_roi     = gray[rh // 4: 3 * rh // 4, rw // 6: 5 * rw // 6]
        bright_frac  = float(np.sum(lens_roi > 240)) / max(lens_roi.size, 1)

        # 3. Tint ─ lens area darker than forehead strip above the ROI
        forehead_strip = rgb_image[max(0, y1 - int(ied * 0.15)):y1, x1:x2]
        if forehead_strip.size > 0:
            fh_brightness   = float(np.mean(cv2.cvtColor(forehead_strip, cv2.COLOR_RGB2GRAY)))
        else:
            fh_brightness   = 128.0
        lens_brightness = float(np.mean(gray[rh // 4: 3 * rh // 4, :]))
        tint_score      = max(0.0, (fh_brightness - lens_brightness) / 255.0)

        combined = edge_score * 6.0 + bright_frac * 3.0 + tint_score * 1.5

        return {
            "glasses_detected": bool(combined > 0.25),
            "confidence":       round(min(1.0, combined), 3),
        }

    def __enter__(self):
        return self

    def __exit__(self, *_):
        self.close()

    # ── Private helpers ────────────────────────────────────────────────────────

    @staticmethod
    def _empty_result(img_w: int, img_h: int) -> dict:
        return {
            "faceDetected": False,
            "faceBox":      {"x": 0, "y": 0, "width": 0, "height": 0, "img_w": img_w, "img_h": img_h},
            "landmarks":    None,
            "confidence":   0.0,
        }


# ── Module-level singleton (lazy init) ────────────────────────────────────────
_detector: Optional[FaceDetector] = None


def get_detector() -> FaceDetector:
    """Return the shared singleton FaceDetector (initialised on first call)."""
    global _detector
    if _detector is None:
        _detector = FaceDetector()
    return _detector
