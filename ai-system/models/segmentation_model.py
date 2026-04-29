"""
models/segmentation_model.py
─────────────────────────────
Background removal using U²-Net via the `rembg` library.

rembg downloads the U²-Net ONNX weights on first use (~170 MB, cached at
~/.u2net/).  Subsequent calls are instant.

Two output modes:
  • transparent (RGBA PNG) — for compositing downstream
  • white background (RGB) — passport-ready

Fine-tuning hook:
  If you later train a custom ONNX segmentation model, drop it in
  models/weights/custom_seg.onnx and set CUSTOM_MODEL_PATH in .env.
  The BackgroundRemover class will load it automatically.
"""
from __future__ import annotations

import os
from typing import Optional

import numpy as np
from PIL import Image
from rembg import new_session, remove

from utils.image_processing import composite_on_white

# Optional: path to a custom ONNX model (see module docstring).
_CUSTOM_MODEL_PATH = os.getenv("CUSTOM_MODEL_PATH", "")

# rembg model to use.  Options: u2net, u2netp (lighter), isnet-general-use, silueta, u2net_human_seg
# u2net_human_seg is trained specifically for human subjects — best for passport photos.
_MODEL_NAME = os.getenv("REMBG_MODEL", "u2net_human_seg")


class BackgroundRemover:
    """
    Wraps rembg / U²-Net for person-background segmentation.

    Usage:
        remover = BackgroundRemover()
        rgb     = remover.remove(rgb_array, white_background=True)
        rgba    = remover.remove(rgb_array, white_background=False)
    """

    def __init__(self) -> None:
        # new_session loads / caches the ONNX model weights.
        # For a custom model, rembg supports loading a local .onnx path.
        if _CUSTOM_MODEL_PATH and os.path.isfile(_CUSTOM_MODEL_PATH):
            self._session = new_session(_CUSTOM_MODEL_PATH)
            self._model_label = f"custom({os.path.basename(_CUSTOM_MODEL_PATH)})"
        else:
            self._session = new_session(_MODEL_NAME)
            self._model_label = _MODEL_NAME

    @property
    def model_label(self) -> str:
        return self._model_label

    def remove(
        self,
        rgb_image: np.ndarray,
        white_background: bool = True,
    ) -> np.ndarray:
        """
        Remove background from an RGB numpy array.

        Parameters
        ----------
        rgb_image        : H×W×3 uint8 RGB array
        white_background : True  → return H×W×3 RGB on white canvas
                           False → return H×W×4 RGBA (transparent background)

        Returns
        -------
        numpy array (uint8)
        """
        pil_input = Image.fromarray(rgb_image)

        # rembg returns an RGBA PIL image with the background made transparent.
        rgba_pil: Image.Image = remove(pil_input, session=self._session)
        rgba_arr = np.array(rgba_pil)  # H×W×4

        if white_background:
            return composite_on_white(rgba_arr)
        return rgba_arr

    def remove_from_bytes(
        self,
        image_bytes: bytes,
        white_background: bool = True,
    ) -> bytes:
        """
        Convenience wrapper: raw image bytes → processed PNG bytes.
        Avoids the numpy ↔ PIL round-trip when the caller already has bytes.
        """
        pil_input = Image.open(__import__("io").BytesIO(image_bytes))
        rgba_pil  = remove(pil_input, session=self._session)

        if white_background:
            rgba_arr = np.array(rgba_pil)
            rgb_arr  = composite_on_white(rgba_arr)
            out      = Image.fromarray(rgb_arr)
            fmt      = "PNG"
        else:
            out = rgba_pil
            fmt = "PNG"

        buf = __import__("io").BytesIO()
        out.save(buf, format=fmt)
        return buf.getvalue()


# ── Module-level singleton ─────────────────────────────────────────────────────
_remover: Optional[BackgroundRemover] = None


def get_remover() -> BackgroundRemover:
    """Return the shared singleton BackgroundRemover (initialised on first call)."""
    global _remover
    if _remover is None:
        _remover = BackgroundRemover()
    return _remover
