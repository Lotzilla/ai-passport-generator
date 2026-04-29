<?php

namespace App\Services\Agents;

use App\Services\PassportRulesService;
use Illuminate\Support\Str;

/**
 * Agent 4 — Crop Agent
 *
 * Responsibility: Position the face correctly and crop to exact passport
 *                 dimensions — regardless of where the face sits in the
 *                 original photo.
 *
 * Algorithm:
 *  1. Compute the source crop window dimensions so the face fills
 *     `face_height_percent` (midpoint) of the output height.
 *  2. Anchor the window so the face is horizontally centred and the
 *     eye line sits at `eye_position_percent` from the top of the output.
 *     This keeps the face visually centred regardless of bounding-box
 *     padding differences across detectors.
 *  3. Create a plain-white GD canvas of those source crop dimensions.
 *     If the computed window extends outside the image bounds, the
 *     overflow areas stay white — face always ends up centred.
 *  4. Paste the (already background-removed) image at the correct
 *     offset on the canvas.
 *  5. Resample the canvas to the exact target pixel dimensions.
 *
 * Input  payload keys: processedImage, faceBox, country
 * Output payload keys: processedImage (path to cropped PNG)
 */
class CropAgent
{
    public static function handle(array $payload): array
    {
        $rules   = (new PassportRulesService())->get($payload['country']);
        $faceBox = $payload['faceBox'];

        [$targetW, $targetH] = $rules['dimensions_px'];

        // Midpoint of allowed face-height range (fraction of photo height)
        $faceHeightTarget = array_sum($rules['face_height_percent']) / 2;

        // Eye-line anchor: the eye line appears at this fraction from the top
        // of the output photo. Switching from face-box-top to eye-line removes
        // the asymmetry caused by MediaPipe's tight bounding box (forehead→chin)
        // which had only 8 % space above the hairline, leaving too much below.
        $eyePositionPct = $rules['eye_position_percent'] ?? 0.40;
        $eyeY           = $faceBox['eye_y'] ?? (int) round($faceBox['y'] + $faceBox['height'] * 0.38);

        $imgW = $faceBox['img_w'];
        $imgH = $faceBox['img_h'];

        // ── 1. Source crop window size ─────────────────────────────────────
        // We want:  faceBox.height / sourceCropH  =  faceHeightTarget
        $sourceCropH = (int) round($faceBox['height'] / $faceHeightTarget);
        $sourceCropW = (int) round($sourceCropH * $targetW / $targetH);

        // ── 2. Crop origin — face always centred & at correct vertical pos ─
        $faceCenterX = $faceBox['x'] + $faceBox['width']  / 2;
        $cropX       = (int) round($faceCenterX - $sourceCropW / 2);
        $cropY       = (int) round($eyeY - $eyePositionPct * $sourceCropH);

        // ── 3. White canvas at source crop dimensions ──────────────────────
        $ext = strtolower(pathinfo($payload['processedImage'], PATHINFO_EXTENSION));
        $src = in_array($ext, ['jpg', 'jpeg'])
            ? imagecreatefromjpeg($payload['processedImage'])
            : imagecreatefrompng($payload['processedImage']);

        $canvas = imagecreatetruecolor($sourceCropW, $sourceCropH);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));

        // ── 4. Paste the intersecting region of the source image ───────────
        // Compute the rectangle that lies inside the actual image bounds.
        $srcX  = max(0, $cropX);
        $srcY  = max(0, $cropY);
        $srcX2 = min($imgW, $cropX + $sourceCropW);
        $srcY2 = min($imgH, $cropY + $sourceCropH);

        // Destination offset on the canvas (positive when crop starts before image edge)
        $dstX  = $srcX - $cropX;   // = max(0, -$cropX)
        $dstY  = $srcY - $cropY;   // = max(0, -$cropY)
        $copyW = $srcX2 - $srcX;
        $copyH = $srcY2 - $srcY;

        if ($copyW > 0 && $copyH > 0) {
            imagecopy($canvas, $src, $dstX, $dstY, $srcX, $srcY, $copyW, $copyH);
        }

        // ── 5. Resample canvas to exact passport dimensions ────────────────
        $output = imagecreatetruecolor($targetW, $targetH);
        imagefill($output, 0, 0, imagecolorallocate($output, 255, 255, 255));
        imagecopyresampled($output, $canvas, 0, 0, 0, 0, $targetW, $targetH, $sourceCropW, $sourceCropH);

        $outputPath = storage_path('app/tmp/' . Str::uuid() . '.png');
        imagepng($output, $outputPath);

        $payload['processedImage'] = $outputPath;

        // ── Update faceBox to output coordinates ───────────────────────────
        $scaleX = $targetW / $sourceCropW;
        $scaleY = $targetH / $sourceCropH;

        $payload['faceBox'] = [
            'x'      => (int) round(($faceBox['x']     - $cropX) * $scaleX),
            'y'      => (int) round(($faceBox['y']     - $cropY) * $scaleY),
            'width'  => (int) round($faceBox['width']  * $scaleX),
            'height' => (int) round($faceBox['height'] * $scaleY),
            'eye_y'  => (int) round(($faceBox['eye_y'] - $cropY) * $scaleY),
            'img_w'  => $targetW,
            'img_h'  => $targetH,
        ];

        return $payload;
    }
}
