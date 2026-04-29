<?php

namespace App\Services\Agents;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Agent 2.5 — Deskew Agent
 *
 * Responsibility: Detect head roll (in-plane rotation) from eye landmarks
 *                 and rotate the image so the eyes are perfectly level —
 *                 exactly as required by all passport standards worldwide.
 *
 * Runs AFTER FaceDetection (which provides eye coordinates via MediaPipe)
 * and BEFORE BackgroundRemoval (so the segmentation model sees a level face).
 *
 * Algorithm:
 *  1. Read leftEye / rightEye pixel coordinates stored by FaceDetectionAgent.
 *  2. Compute the roll angle:  atan2(Δy, Δx) across the eye line.
 *  3. Rotate the image by -rollAngle using GD imagerotate() so eyes become
 *     exactly horizontal.
 *  4. Apply the same rotation matrix to faceBox corners and all landmarks
 *     so downstream agents have correct coordinates in the new image space.
 *  5. Re-run face detection on the rotated image to get a precise, fresh
 *     bounding box (the transformed box is also kept as a fast fallback).
 *
 * If no landmarks are present (Python service unavailable), the agent
 * passes through completely unchanged.
 */
class DeskewAgent
{
    /** Skip if eyes are already within this many degrees of level. */
    private const MIN_ANGLE_DEG = 1.5;

    /** Skip if tilt exceeds this — pitch/yaw issues need re-take, not rotation. */
    private const MAX_ANGLE_DEG = 30.0;

    public static function handle(array $payload): array
    {
        $landmarks = $payload['faceLandmarks'] ?? null;

        if (! $landmarks || ! isset($landmarks['leftEye'], $landmarks['rightEye'])) {
            Log::info('[Deskew] No eye landmarks available — skipping');
            return $payload;
        }

        // Landmarks are [x, y] arrays (from MediaPipe via Python service)
        $leftEye  = $landmarks['leftEye'];   // [x, y]
        $rightEye = $landmarks['rightEye'];  // [x, y]

        // Roll angle: positive = right eye lower than left eye (face rolled CW on screen)
        $tiltDeg = rad2deg(atan2(
            (float) $rightEye[1] - (float) $leftEye[1],
            (float) $rightEye[0] - (float) $leftEye[0]
        ));

        if (abs($tiltDeg) < self::MIN_ANGLE_DEG) {
            Log::info('[Deskew] Already level — skipping', ['tilt_deg' => round($tiltDeg, 2)]);
            return $payload;
        }

        if (abs($tiltDeg) > self::MAX_ANGLE_DEG) {
            Log::warning('[Deskew] Tilt too large to auto-correct safely', ['tilt_deg' => round($tiltDeg, 2)]);
            return $payload;
        }

        Log::info('[Deskew] Correcting head tilt', ['tilt_deg' => round($tiltDeg, 2)]);

        // ── Load source image ──────────────────────────────────────────────
        $imgPath = $payload['processedImage'];
        $ext     = strtolower(pathinfo($imgPath, PATHINFO_EXTENSION));
        $src     = in_array($ext, ['jpg', 'jpeg'])
            ? imagecreatefromjpeg($imgPath)
            : imagecreatefrompng($imgPath);

        $origW = imagesx($src);
        $origH = imagesy($src);

        // Preserve alpha if present
        imagesavealpha($src, true);

        // ── Rotate ────────────────────────────────────────────────────────
        // imagerotate uses CCW-positive convention.
        // correctionDeg = -tiltDeg: negative value = CW rotation = levels a right-low tilt.
        $correctionDeg = -$tiltDeg;
        $correctionRad = deg2rad($correctionDeg);

        $white   = imagecolorallocate($src, 255, 255, 255);
        $rotated = imagerotate($src, $correctionDeg, $white);
        unset($src);

        $newW = imagesx($rotated);
        $newH = imagesy($rotated);

        // ── Save rotated image ─────────────────────────────────────────────
        $outPath = storage_path('app/tmp/' . Str::uuid() . '.png');
        imagepng($rotated, $outPath);
        unset($rotated);

        $payload['processedImage'] = $outPath;

        // ── Transform faceBox ─────────────────────────────────────────────
        $faceBox = $payload['faceBox'];
        if ($faceBox) {
            $corners = [
                [(float) $faceBox['x'],                        (float) $faceBox['y']],
                [(float) $faceBox['x'] + (float) $faceBox['width'],  (float) $faceBox['y']],
                [(float) $faceBox['x'],                        (float) $faceBox['y'] + (float) $faceBox['height']],
                [(float) $faceBox['x'] + (float) $faceBox['width'],  (float) $faceBox['y'] + (float) $faceBox['height']],
            ];

            $xs = [];
            $ys = [];
            foreach ($corners as [$px, $py]) {
                [$nx, $ny] = self::rotatePoint($px, $py, $origW, $origH, $newW, $newH, $correctionRad);
                $xs[] = $nx;
                $ys[] = $ny;
            }

            // Eye midpoint in new image space
            $eyeMidX = ((float) $leftEye[0] + (float) $rightEye[0]) / 2.0;
            $eyeMidY = ((float) $leftEye[1] + (float) $rightEye[1]) / 2.0;
            [, $newEyeY] = self::rotatePoint($eyeMidX, $eyeMidY, $origW, $origH, $newW, $newH, $correctionRad);

            $newBoxX = max(0, (int) round(min($xs)));
            $newBoxY = max(0, (int) round(min($ys)));

            $payload['faceBox'] = [
                'x'      => $newBoxX,
                'y'      => $newBoxY,
                'width'  => (int) round(max($xs) - min($xs)),
                'height' => (int) round(max($ys) - min($ys)),
                'eye_y'  => (int) round($newEyeY),
                'img_w'  => $newW,
                'img_h'  => $newH,
            ];
        }

        // ── Transform all landmarks ────────────────────────────────────────
        foreach (['leftEye', 'rightEye', 'noseTip', 'chin'] as $key) {
            if (! empty($landmarks[$key])) {
                [$nx, $ny] = self::rotatePoint(
                    (float) $landmarks[$key][0],
                    (float) $landmarks[$key][1],
                    $origW, $origH, $newW, $newH, $correctionRad
                );
                $landmarks[$key] = [(int) round($nx), (int) round($ny)];
            }
        }
        $payload['faceLandmarks'] = $landmarks;

        // ── Re-run face detection on the straightened image ────────────────
        // This gives downstream agents (BackgroundRemoval, Crop) the most
        // precise bounding box from a level face rather than the transformed box.
        $pythonApiUrl = config('services.ai_pipeline.url', '');
        if (! empty($pythonApiUrl)) {
            $refreshed = self::refreshFaceBox($outPath, $pythonApiUrl, $payload['faceBox']);
            $payload['faceBox']      = $refreshed['faceBox'];
            $payload['faceLandmarks'] = $refreshed['landmarks'];
        }

        Log::info('[Deskew] Complete', [
            'tilt_deg'   => round($tiltDeg, 2),
            'new_w'      => $newW,
            'new_h'      => $newH,
        ]);

        return $payload;
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Transform a point from the original image space into the rotated image space.
     *
     * Uses the standard 2-D rotation formula (CCW by thetaRad).
     * Because imagerotate() uses the same CCW-positive convention, passing
     * correctionRad directly yields the correct forward mapping.
     *
     * @param float $thetaRad  The angle passed to imagerotate() (= deg2rad(correctionDeg))
     */
    private static function rotatePoint(
        float $x, float $y,
        int $origW, int $origH,
        int $newW, int $newH,
        float $thetaRad
    ): array {
        $cx  = $origW / 2.0;
        $cy  = $origH / 2.0;
        $cx2 = $newW  / 2.0;
        $cy2 = $newH  / 2.0;

        $dx   = $x - $cx;
        $dy   = $y - $cy;
        $cosT = cos($thetaRad);
        $sinT = sin($thetaRad);

        return [
            $cx2 + $dx * $cosT - $dy * $sinT,
            $cy2 + $dx * $sinT + $dy * $cosT,
        ];
    }

    /**
     * Re-detect the face on the freshly rotated image via the Python service.
     * Falls back to the mathematically transformed box if the call fails.
     */
    private static function refreshFaceBox(
        string $imagePath,
        string $baseUrl,
        array  $fallbackBox
    ): array {
        try {
            $client   = new \GuzzleHttp\Client(['timeout' => 10]);
            $response = $client->post(rtrim($baseUrl, '/') . '/detect-face', [
                'multipart' => [
                    ['name' => 'image', 'contents' => fopen($imagePath, 'r')],
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);

            if (empty($body['faceDetected'])) {
                return ['faceBox' => $fallbackBox, 'landmarks' => []];
            }

            $fb  = $body['faceBox'];
            $lm  = $body['landmarks'] ?? [];

            $eyeY = $fb['y'] + (int) ($fb['height'] * 0.35);
            if (! empty($lm['leftEye']) && ! empty($lm['rightEye'])) {
                $eyeY = (int) (($lm['leftEye'][1] + $lm['rightEye'][1]) / 2);
            }

            return [
                'faceBox' => [
                    'x'      => (int) $fb['x'],
                    'y'      => (int) $fb['y'],
                    'width'  => (int) $fb['width'],
                    'height' => (int) $fb['height'],
                    'eye_y'  => $eyeY,
                    'img_w'  => (int) $fb['img_w'],
                    'img_h'  => (int) $fb['img_h'],
                ],
                'landmarks' => $lm,
            ];

        } catch (\Throwable $e) {
            Log::warning('[Deskew] Face re-detection failed — using transformed box', [
                'error' => $e->getMessage(),
            ]);
            return ['faceBox' => $fallbackBox, 'landmarks' => []];
        }
    }
}
