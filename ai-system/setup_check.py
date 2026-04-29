#!/usr/bin/env python3
"""
setup_check.py
──────────────
Run this once after installing requirements to verify every dependency
loads correctly and the models initialise without errors.

Usage:
    python setup_check.py
"""
import sys


def check(label: str, fn):
    try:
        fn()
        print(f"  [OK]  {label}")
        return True
    except Exception as exc:
        print(f"  [FAIL] {label}: {exc}")
        return False


def main():
    print("\n── Dependency checks ─────────────────────────────────────────")
    ok = True

    ok &= check("numpy",     lambda: __import__("numpy"))
    ok &= check("cv2",       lambda: __import__("cv2"))
    ok &= check("mediapipe", lambda: __import__("mediapipe"))
    ok &= check("PIL",       lambda: __import__("PIL"))
    ok &= check("rembg",     lambda: __import__("rembg"))
    ok &= check("fastapi",   lambda: __import__("fastapi"))
    ok &= check("uvicorn",   lambda: __import__("uvicorn"))

    if not ok:
        print("\nSome dependencies are missing. Run:\n  pip install -r requirements.txt\n")
        sys.exit(1)

    print("\n── Model initialisation checks ───────────────────────────────")

    # Face detector
    ok &= check(
        "FaceDetector (MediaPipe)",
        lambda: __import__("models.face_model", fromlist=["get_detector"]).get_detector(),
    )

    # Background remover — downloads U²-Net weights on first call (~170 MB).
    print("  [..] BackgroundRemover (U²-Net) — first run downloads ~170 MB, please wait…")
    ok &= check(
        "BackgroundRemover (rembg/U²-Net)",
        lambda: __import__(
            "models.segmentation_model", fromlist=["get_remover"]
        ).get_remover(),
    )

    print()
    if ok:
        print("All checks passed. Start the server with:\n")
        print("  cd ai-system")
        print("  uvicorn api.main:app --host 0.0.0.0 --port 8000 --reload\n")
    else:
        print("Some checks failed. See errors above.\n")
        sys.exit(1)


if __name__ == "__main__":
    main()
