"""
utils/image_processing.py
─────────────────────────
Low-level image helpers shared across models and services.
All functions operate on numpy arrays (BGR for OpenCV, RGB for everything else).
"""
from __future__ import annotations

import base64
import io
from typing import Tuple

import cv2
import numpy as np
from PIL import Image


# ── Decoding ───────────────────────────────────────────────────────────────────

def bytes_to_numpy(image_bytes: bytes) -> np.ndarray:
    """Decode raw image bytes → RGB numpy array (H, W, 3)."""
    buf = np.frombuffer(image_bytes, dtype=np.uint8)
    bgr = cv2.imdecode(buf, cv2.IMREAD_COLOR)
    if bgr is None:
        raise ValueError("Could not decode image bytes — unsupported format or corrupt data.")
    return cv2.cvtColor(bgr, cv2.COLOR_BGR2RGB)


def base64_to_numpy(b64_string: str) -> np.ndarray:
    """Decode a base64-encoded image string → RGB numpy array."""
    # Strip data-URL prefix if present (e.g. "data:image/jpeg;base64,...")
    if "," in b64_string:
        b64_string = b64_string.split(",", 1)[1]
    raw = base64.b64decode(b64_string)
    return bytes_to_numpy(raw)


def pil_to_numpy(pil_image: Image.Image) -> np.ndarray:
    """PIL Image → RGB numpy array."""
    return np.array(pil_image.convert("RGB"))


# ── Encoding ───────────────────────────────────────────────────────────────────

def numpy_to_pil(array: np.ndarray) -> Image.Image:
    """RGB numpy array → PIL Image."""
    return Image.fromarray(array.astype(np.uint8))


def numpy_to_bytes(array: np.ndarray, fmt: str = "PNG") -> bytes:
    """RGB numpy array → raw image bytes in the given format."""
    pil = numpy_to_pil(array)
    buf = io.BytesIO()
    pil.save(buf, format=fmt)
    return buf.getvalue()


def numpy_to_base64(array: np.ndarray, fmt: str = "PNG") -> str:
    """RGB numpy array → base64 string."""
    raw = numpy_to_bytes(array, fmt)
    return base64.b64encode(raw).decode("utf-8")


def rgba_to_bytes(array: np.ndarray, fmt: str = "PNG") -> bytes:
    """RGBA numpy array → raw PNG bytes (preserves transparency)."""
    pil = Image.fromarray(array.astype(np.uint8), mode="RGBA")
    buf = io.BytesIO()
    pil.save(buf, format=fmt)
    return buf.getvalue()


# ── Resizing / Padding ─────────────────────────────────────────────────────────

def resize_keep_aspect(
    image: np.ndarray,
    max_size: int = 1024,
) -> Tuple[np.ndarray, float]:
    """
    Resize image so its longest side ≤ max_size.
    Returns (resized_array, scale_factor).
    scale_factor < 1 means the image was shrunk; == 1 means unchanged.
    """
    h, w = image.shape[:2]
    scale = min(1.0, max_size / max(h, w))
    if scale < 1.0:
        new_w = max(1, int(w * scale))
        new_h = max(1, int(h * scale))
        image = cv2.resize(image, (new_w, new_h), interpolation=cv2.INTER_AREA)
    return image, scale


def composite_on_white(rgba_array: np.ndarray) -> np.ndarray:
    """
    Flatten an RGBA image onto a white background → RGB array.
    Used to convert transparent-background output to passport-ready white.
    """
    if rgba_array.shape[2] != 4:
        return rgba_array  # already RGB, nothing to do

    r, g, b, a = (rgba_array[:, :, i].astype(np.float32) for i in range(4))

    # Zero out near-transparent pixels before compositing — eliminates the grey
    # fringe/halo caused by soft alpha edge leakage from the segmentation mask.
    a = np.where(a < 15, 0.0, a)

    alpha = a / 255.0

    white = np.ones_like(r) * 255.0
    out_r = (r * alpha + white * (1 - alpha)).clip(0, 255).astype(np.uint8)
    out_g = (g * alpha + white * (1 - alpha)).clip(0, 255).astype(np.uint8)
    out_b = (b * alpha + white * (1 - alpha)).clip(0, 255).astype(np.uint8)

    return np.stack([out_r, out_g, out_b], axis=2)
