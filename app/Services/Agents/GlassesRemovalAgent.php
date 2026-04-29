<?php

namespace App\Services\Agents;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Agent 2.75 — Glasses Removal Agent
 *
 * Responsibility: Detect and remove eyeglasses from the photo using the
 *                 ClipDrop Cleanup API (AI inpainting by Stability AI).
 *
 * Runs AFTER DeskewAgent (landmarks are in the correct rotated space)
 * and BEFORE BackgroundRemovalAgent (segmentation works better without frames).
 *
 * Pipeline:
 *   1. Skip entirely if CLIPDROP_API_KEY is not configured.
 *   2. Ask the Python AI service (/detect-glasses) whether glasses are present.
 *      Falls back to a PHP pixel-sampling heuristic if Python is offline.
 *   3. If glasses are detected, build a white-on-black PNG mask covering:
 *        • Left lens (ellipse around left-eye landmark)
 *        • Right lens (ellipse around right-eye landmark)
 *        • Nose bridge (rectangle between inner lens edges)
 *        • Temple extensions (strips to the frame edges)
 *   4. POST { image_file, mask_file } to ClipDrop Cleanup v1.
 *      The API inpaints the masked region with AI-generated natural skin/face.
 *   5. If any step fails, the original image is kept and the pipeline continues.
 *
 * Config:
 *   CLIPDROP_API_KEY — required; get a free key at https://clipdrop.co/apis
 *
 * Notes:
 *   • Passport standards (ICAO Doc 9303) prohibit glasses in biometric photos.
 *   • ClipDrop mask convention: white pixel = inpaint this area.
 *   • The processed image is overwritten in-place on success; a new temp file
 *     is NOT needed because the mask is the only auxiliary file.
 */
class GlassesRemovalAgent
{
    public static function handle(array $payload): array
    {
        $apiKey = config('services.clipdrop.key', '');

        // Nothing to do without the API key
        if (empty($apiKey)) {
            Log::info('[GlassesRemoval] Skipped — CLIPDROP_API_KEY not configured.');
            return $payload;
        }

        $faceBox   = $payload['faceBox']       ?? null;
        $landmarks = $payload['faceLandmarks'] ?? null;
        $imagePath = $payload['processedImage'];

        if (! $faceBox) {
            Log::info('[GlassesRemoval] No faceBox in payload — skipping.');
            return $payload;
        }

        // ── Step 1: Detect glasses ─────────────────────────────────────────
        $detected = self::detectGlasses($imagePath, $landmarks, $faceBox);

        if (! $detected) {
            Log::info('[GlassesRemoval] No glasses detected — skipping.');
            return $payload;
        }

        Log::info('[GlassesRemoval] Glasses detected — building inpainting mask.');

        // ── Step 2: Build mask PNG ─────────────────────────────────────────
        $maskPath = self::buildMask($imagePath, $landmarks, $faceBox);

        if (! $maskPath) {
            Log::warning('[GlassesRemoval] Mask generation failed — skipping.');
            return $payload;
        }

        // ── Step 3: Call ClipDrop Cleanup API ─────────────────────────────
        $success = self::callClipDrop($imagePath, $maskPath, $apiKey);

        @unlink($maskPath);

        if ($success) {
            Log::info('[GlassesRemoval] Glasses removed successfully via ClipDrop.');
        } else {
            Log::warning('[GlassesRemoval] ClipDrop call failed — original image retained.');
        }

        return $payload;
    }

    // ── Glasses detection ──────────────────────────────────────────────────────

    /**
     * Try Python /detect-glasses first; fall back to a PHP pixel heuristic.
     */
    private static function detectGlasses(
        string  $imagePath,
        ?array  $landmarks,
        array   $faceBox
    ): bool {
        $pythonUrl = config('services.ai_pipeline.url', '');

        if (! empty($pythonUrl)) {
            try {
                $client   = new Client(['timeout' => 10]);
                $response = $client->post(rtrim($pythonUrl, '/') . '/detect-glasses', [
                    'multipart' => [
                        ['name' => 'image', 'contents' => fopen($imagePath, 'r')],
                    ],
                ]);
                $body = json_decode((string) $response->getBody(), true);

                if (isset($body['glasses_detected'])) {
                    Log::info('[GlassesRemoval] Python detection result', [
                        'detected'   => $body['glasses_detected'],
                        'confidence' => $body['confidence'] ?? null,
                    ]);
                    return (bool) $body['glasses_detected'];
                }
            } catch (\Throwable $e) {
                Log::warning('[GlassesRemoval] Python service unavailable, using PHP heuristic.', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return self::phpHeuristicDetect($imagePath, $landmarks, $faceBox);
    }

    /**
     * Pure-PHP glasses heuristic.
     *
     * Samples the eye-region for:
     *   • Dark pixel density  — glasses frames / tinted lenses
     *   • Bright pixel density — lens reflections (specular highlights)
     *
     * Returns true when the combined signal exceeds empirical thresholds.
     */
    private static function phpHeuristicDetect(
        string $imagePath,
        ?array $landmarks,
        array  $faceBox
    ): bool {
        [$imgW, $imgH] = getimagesize($imagePath);
        $ext           = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));

        $gd = match (true) {
            in_array($ext, ['jpg', 'jpeg']) => imagecreatefromjpeg($imagePath),
            $ext === 'png'                  => imagecreatefrompng($imagePath),
            default                         => false,
        };

        if (! $gd) {
            return false;
        }

        // Define eye-region bounding box
        if ($landmarks && ! empty($landmarks['leftEye']) && ! empty($landmarks['rightEye'])) {
            $lx  = (int) $landmarks['leftEye'][0];
            $rx  = (int) $landmarks['rightEye'][0];
            $ey  = (int) (($landmarks['leftEye'][1] + $landmarks['rightEye'][1]) / 2);
            $ied = max(1, $rx - $lx);  // inter-eye distance

            $rx1 = max(0,     $lx - (int) ($ied * 0.55));
            $rx2 = min($imgW, $rx + (int) ($ied * 0.55));
            $ry1 = max(0,     $ey - (int) ($ied * 0.38));
            $ry2 = min($imgH, $ey + (int) ($ied * 0.32));
        } else {
            // Fallback: estimate from faceBox (eyes sit ~35% from top of box)
            $eyeY = $faceBox['y'] + (int) ($faceBox['height'] * 0.35);
            $rx1  = max(0,     $faceBox['x'] + (int) ($faceBox['width'] * 0.05));
            $rx2  = min($imgW, $faceBox['x'] + $faceBox['width'] - (int) ($faceBox['width'] * 0.05));
            $ry1  = max(0,     $eyeY - (int) ($faceBox['height'] * 0.12));
            $ry2  = min($imgH, $eyeY + (int) ($faceBox['height'] * 0.10));
        }

        if ($rx2 <= $rx1 || $ry2 <= $ry1) {
            imagedestroy($gd);
            return false;
        }

        $dark   = 0;
        $bright = 0;
        $total  = 0;
        $step   = max(1, (int) (($rx2 - $rx1) / 25));

        for ($x = $rx1; $x < $rx2; $x += $step) {
            for ($y = $ry1; $y < $ry2; $y += $step) {
                $rgb = imagecolorat($gd, $x, $y);
                $r   = ($rgb >> 16) & 0xFF;
                $g   = ($rgb >> 8)  & 0xFF;
                $b   =  $rgb        & 0xFF;
                $lum = (int) (0.299 * $r + 0.587 * $g + 0.114 * $b);

                if ($lum < 55)  { $dark++;   }
                if ($lum > 230) { $bright++;  }
                $total++;
            }
        }

        imagedestroy($gd);

        if ($total === 0) {
            return false;
        }

        // Dark frames / tinted lenses OR specular reflections from lens glass
        return ($dark / $total) > 0.18 || ($bright / $total) > 0.08;
    }

    // ── Mask generation ────────────────────────────────────────────────────────

    /**
     * Build a white-on-black PNG mask covering the glasses region.
     *
     * White pixel = "remove this area" (ClipDrop convention).
     * The mask covers lens ovals, nose bridge, and temple strips.
     */
    private static function buildMask(
        string $imagePath,
        ?array $landmarks,
        array  $faceBox
    ): ?string {
        [$imgW, $imgH] = getimagesize($imagePath);

        $mask  = imagecreatetruecolor($imgW, $imgH);
        $black = imagecolorallocate($mask, 0,   0,   0);
        $white = imagecolorallocate($mask, 255, 255, 255);
        imagefill($mask, 0, 0, $black);

        if ($landmarks && ! empty($landmarks['leftEye']) && ! empty($landmarks['rightEye'])) {
            $lx  = (int) $landmarks['leftEye'][0];
            $ly  = (int) $landmarks['leftEye'][1];
            $rx  = (int) $landmarks['rightEye'][0];
            $ry  = (int) $landmarks['rightEye'][1];
            $ied = max(1, $rx - $lx);

            $radX  = (int) ($ied * 0.40);   // horizontal radius of each lens oval
            $radY  = (int) ($ied * 0.30);   // vertical radius of each lens oval

            // Left lens
            imagefilledellipse($mask, $lx, $ly, $radX * 2, $radY * 2, $white);

            // Right lens
            imagefilledellipse($mask, $rx, $ry, $radX * 2, $radY * 2, $white);

            // Nose bridge (rectangle connecting the inner lens edges)
            $eyeMidY = (int) (($ly + $ry) / 2);
            imagefilledrectangle(
                $mask,
                $lx + (int) ($radX * 0.55),
                $eyeMidY - (int) ($radY * 0.45),
                $rx - (int) ($radX * 0.55),
                $eyeMidY + (int) ($radY * 0.45),
                $white
            );

            // Temple strips (extend outward from outer lens edges)
            $tY1 = min($ly, $ry) - (int) ($radY * 0.4);
            $tY2 = max($ly, $ry) + (int) ($radY * 0.4);

            // Left temple
            imagefilledrectangle(
                $mask,
                max(0, $lx - (int) ($ied * 0.65)),
                $tY1,
                $lx - $radX + 4,
                $tY2,
                $white
            );

            // Right temple
            imagefilledrectangle(
                $mask,
                $rx + $radX - 4,
                $tY1,
                min($imgW, $rx + (int) ($ied * 0.65)),
                $tY2,
                $white
            );
        } else {
            // No landmarks — use faceBox to estimate the glasses strip
            $eyeY = $faceBox['y'] + (int) ($faceBox['height'] * 0.38);
            imagefilledrectangle(
                $mask,
                max(0,     $faceBox['x'] + (int) ($faceBox['width'] * 0.03)),
                max(0,     $eyeY - (int) ($faceBox['height'] * 0.13)),
                min($imgW, $faceBox['x'] + $faceBox['width'] - (int) ($faceBox['width'] * 0.03)),
                min($imgH, $eyeY + (int) ($faceBox['height'] * 0.10)),
                $white
            );
        }

        $maskPath = storage_path('app/tmp/glasses_mask_' . Str::uuid() . '.png');
        @mkdir(dirname($maskPath), 0755, true);
        imagepng($mask, $maskPath);
        imagedestroy($mask);

        return file_exists($maskPath) ? $maskPath : null;
    }

    // ── ClipDrop Cleanup API ───────────────────────────────────────────────────

    /**
     * POST the image + mask to ClipDrop Cleanup v1.
     * On success the cleaned PNG bytes are written back to $imagePath.
     *
     * API docs: https://clipdrop.co/apis/docs/cleanup
     * Mask convention: white = inpaint, black = keep.
     */
    private static function callClipDrop(
        string $imagePath,
        string $maskPath,
        string $apiKey
    ): bool {
        try {
            $client   = new Client(['timeout' => 45]);
            $response = $client->post('https://clipdrop-api.co/cleanup/v1', [
                'headers'   => ['x-api-key' => $apiKey],
                'multipart' => [
                    [
                        'name'     => 'image_file',
                        'contents' => fopen($imagePath, 'r'),
                        'filename' => 'photo.png',
                    ],
                    [
                        'name'     => 'mask_file',
                        'contents' => fopen($maskPath, 'r'),
                        'filename' => 'mask.png',
                    ],
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                Log::warning('[GlassesRemoval] ClipDrop returned non-200', [
                    'status' => $response->getStatusCode(),
                    'body'   => substr((string) $response->getBody(), 0, 200),
                ]);
                return false;
            }

            $cleanedBytes = (string) $response->getBody();

            if (empty($cleanedBytes)) {
                Log::warning('[GlassesRemoval] ClipDrop returned empty body.');
                return false;
            }

            file_put_contents($imagePath, $cleanedBytes);
            return true;

        } catch (\Throwable $e) {
            Log::warning('[GlassesRemoval] ClipDrop API exception.', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
