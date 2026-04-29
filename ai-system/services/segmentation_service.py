"""
services/segmentation_service.py
──────────────────────────────────
Thin service layer between the API endpoints and the BackgroundRemover model.
"""
from __future__ import annotations

from models.segmentation_model import get_remover
from utils.image_processing import (
    bytes_to_numpy,
    numpy_to_bytes,
    rgba_to_bytes,
)


def process_segmentation(
    image_bytes: bytes,
    white_background: bool = True,
) -> tuple[bytes, dict]:
    """
    Remove the background from a raw image and return processed PNG bytes
    together with a metadata dict.

    Parameters
    ----------
    image_bytes      : raw bytes of the input image (JPEG / PNG / WebP)
    white_background : True  → output is RGB on white canvas
                       False → output is RGBA with transparent background

    Returns
    -------
    (png_bytes, metadata_dict)

    metadata_dict keys:
        success          : bool
        model            : str  — model name used (e.g. "u2net")
        white_background : bool — mirrors the input flag
        width / height   : int  — output dimensions
        error            : str  — only present on failure
    """
    try:
        rgb = bytes_to_numpy(image_bytes)
    except ValueError as exc:
        return b"", {"success": False, "error": str(exc)}

    try:
        remover = get_remover()
        result  = remover.remove(rgb, white_background=white_background)
    except Exception as exc:
        return b"", {"success": False, "error": f"Segmentation failed: {exc}"}

    h, w = result.shape[:2]

    if white_background:
        out_bytes = numpy_to_bytes(result, fmt="PNG")
    else:
        out_bytes = rgba_to_bytes(result, fmt="PNG")

    meta = {
        "success":          True,
        "model":            remover.model_label,
        "white_background": white_background,
        "width":            w,
        "height":           h,
    }
    return out_bytes, meta
