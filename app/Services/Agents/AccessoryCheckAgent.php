<?php

namespace App\Services\Agents;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * Agent 3.5 — Accessory Check Agent
 *
 * Responsibility: Detect forbidden accessories in the photo and abort the
 *                 pipeline with a clear, actionable error message.
 *
 * Checked accessories (ICAO Doc 9303 requirements):
 *   • Eyeglasses / sunglasses  — not permitted in biometric passport photos
 *   • Hats / head coverings    — not permitted (religious exceptions are
 *                                handled by the compliance layer, not here)
 *
 * Runs AFTER BackgroundRemovalAgent so the background is white — this makes
 * both heuristics significantly more accurate since non-white pixels in the
 * relevant regions are either part of the subject or accessories.
 *
 * Detection priority:
 *   1. Python AI service (/detect-glasses) for glasses — highest accuracy
 *   2. PHP pixel-sampling heuristics — offline fallback
 *
 * On detection, sets:
 *   faceDetected     => false   (causes the pipeline gate to fire)
 *   status           => 'failed'
 *   errors[]         => human-readable message per accessory found
 *   accessoryTypes[] => ['glasses'] | ['hat'] | ['glasses', 'hat']
 */
class AccessoryCheckAgent
{
    // ── Tuning constants ────────────────────────────────────────────────────────

    /** Min fraction of eye-region pixels that must be dark to flag glasses. */
    private const GLASSES_DARK_FRACTION = 0.14;

    /** Min fraction of eye-region pixels that must be specular-bright to flag glasses. */
    private const GLASSES_BRIGHT_FRACTION = 0.06;

    /**
     * Minimum ratio of (non-white span at face-top) / (face width)
     * to flag a hat brim.
     */
    private const HAT_BRIM_RATIO = 1.30;

    /**
     * Minimum fraction of the above-forehead region that must be non-white
     * AND span at least 50 % of image width to flag a hat.
     */
    private const HAT_AREA_FRACTION = 0.22;

    /** A pixel is "nearly white" (background) if all channels are above this. */
    private const WHITE_THRESHOLD = 230;

    /** A pixel is "dark" (possible glasses frame / tinted lens) if luminosity is below this. */
    private const DARK_LUM_THRESHOLD = 75;

    // ── Entry point ─────────────────────────────────────────────────────────────

    public static function handle(array $payload): array
    {
        $faceBox   = $payload['faceBox']       ?? null;
        $landmarks = $payload['faceLandmarks'] ?? null;
        $imagePath = $payload['processedImage'];

        if (! $faceBox) {
            Log::info('[AccessoryCheck] No faceBox in payload — skipping.');
            return $payload;
        }

        $issues = [];

        // ── Glasses ────────────────────────────────────────────────────────────
        if (self::detectGlasses($imagePath, $landmarks, $faceBox)) {
            $issues[] = 'glasses';
            $payload['errors'][] =
                'Glasses detected. Please remove your glasses and retake the photo.';
            Log::warning('[AccessoryCheck] Glasses detected — aborting pipeline.');
        }

        // ── Hat / head covering ────────────────────────────────────────────────
        if (self::detectHat($imagePath, $faceBox)) {
            $issues[] = 'hat';
            $payload['errors'][] =
                'Hat or head covering detected. Please remove all headwear and retake the photo.';
            Log::warning('[AccessoryCheck] Hat detected — aborting pipeline.');
        }

        // ── Abort if anything was found ────────────────────────────────────────
        if (! empty($issues)) {
            $payload['accessoryDetected'] = true;
            $payload['accessoryTypes']    = $issues;
            $payload['faceDetected']      = false;   // triggers pipeline gate
            $payload['status']            = 'failed';
        }

        return $payload;
    }

    // ── Glasses detection ────────────────────────────────────────────────────────

    /**
     * Try the Python /detect-glasses endpoint first; fall back to PHP heuristic.
     */
    private static function detectGlasses(
        string $imagePath,
        ?array $landmarks,
        array  $faceBox
    ): bool {
        $pythonUrl = config('services.ai_pipeline.url', '');

        if (! empty($pythonUrl)) {
            try {
                $client   = new Client(['timeout' => 10]);
                $response = $client->post(
                    rtrim($pythonUrl, '/') . '/detect-glasses',
                    ['multipart' => [['name' => 'image', 'contents' => fopen($imagePath, 'r')]]]
                );
                $body = json_decode((string) $response->getBody(), true);

                if (isset($body['glasses_detected'])) {
                    Log::info('[AccessoryCheck] Python glasses result', [
                        'detected'   => $body['glasses_detected'],
                        'confidence' => $body['confidence'] ?? null,
                    ]);
                    return (bool) $body['glasses_detected'];
                }
            } catch (\Throwable $e) {
                Log::warning('[AccessoryCheck] Python service unavailable — using PHP heuristic.', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return self::phpGlassesHeuristic($imagePath, $landmarks, $faceBox);
    }

    /**
     * Pure-PHP glasses heuristic.
     *
     * Samples the region spanning both eyes for:
     *   • Dark pixels   — glasses frames / tinted lenses
     *   • Bright pixels — lens specular reflections
     *
     * The background is white at this point (after BackgroundRemoval), so
     * dark pixels in the eye region are strong evidence of glasses.
     */
    private static function phpGlassesHeuristic(
        string $imagePath,
        ?array $landmarks,
        array  $faceBox
    ): bool {
        $gd = self::loadGd($imagePath);
        if (! $gd) {
            return false;
        }

        $imgW = $faceBox['img_w'];
        $imgH = $faceBox['img_h'];
        $fx   = $faceBox['x'];
        $fy   = $faceBox['y'];
        $fw   = $faceBox['width'];
        $fh   = $faceBox['height'];

        // Eye region coordinates
        if (! empty($landmarks['leftEye']) && ! empty($landmarks['rightEye'])) {
            $lx = (int) $landmarks['leftEye'][0];
            $rx = (int) $landmarks['rightEye'][0];
            $ey = (int) (($landmarks['leftEye'][1] + $landmarks['rightEye'][1]) / 2);
        } else {
            // Estimate from faceBox (eyes are roughly 38 % down from face top)
            $lx = (int) ($fx + $fw * 0.28);
            $rx = (int) ($fx + $fw * 0.72);
            $ey = (int) ($fy + $fh * 0.38);
        }

        $ied  = max(1, $rx - $lx);   // inter-eye distance
        $x1   = max(0, $lx - (int) ($ied * 0.5));
        $x2   = min($imgW - 1, $rx + (int) ($ied * 0.5));
        $y1   = max(0, $ey - (int) ($ied * 0.35));
        $y2   = min($imgH - 1, $ey + (int) ($ied * 0.30));

        if ($x2 - $x1 < 4 || $y2 - $y1 < 4) {
            imagedestroy($gd);
            return false;
        }

        $total      = 0;
        $darkCount  = 0;
        $brightCount = 0;

        for ($y = $y1; $y <= $y2; $y += 2) {
            for ($x = $x1; $x <= $x2; $x += 2) {
                $rgb  = imagecolorat($gd, $x, $y);
                $r    = ($rgb >> 16) & 0xFF;
                $g    = ($rgb >> 8)  & 0xFF;
                $b    = $rgb         & 0xFF;
                $lum  = (int) (0.299 * $r + 0.587 * $g + 0.114 * $b);

                $total++;
                if ($lum < self::DARK_LUM_THRESHOLD) {
                    $darkCount++;
                }
                if ($lum > 230 && $r > 220 && $g > 220 && $b > 220) {
                    // Near-pure-white = likely background leaked in; skip
                } elseif ($lum > 200) {
                    $brightCount++;
                }
            }
        }

        imagedestroy($gd);

        if ($total === 0) {
            return false;
        }

        $darkFrac   = $darkCount  / $total;
        $brightFrac = $brightCount / $total;

        Log::info('[AccessoryCheck] Glasses PHP heuristic', [
            'dark_frac'   => round($darkFrac, 3),
            'bright_frac' => round($brightFrac, 3),
        ]);

        return $darkFrac > self::GLASSES_DARK_FRACTION
            || $brightFrac > self::GLASSES_BRIGHT_FRACTION;
    }

    // ── Hat / head covering detection ────────────────────────────────────────────

    /**
     * Detects hats by analysing two signals (with white background):
     *
     * Signal 1 — Brim width:
     *   Scans horizontally at y = face_top. If non-white pixels extend more
     *   than HAT_BRIM_RATIO × face_width, it means something (a hat brim) is
     *   wider than the face itself.
     *
     * Signal 2 — Above-forehead coverage:
     *   Scans the rectangular region above the forehead. If a large fraction
     *   of those pixels are non-white AND the non-white region spans most of
     *   the image width, it's a hat (hair alone is narrower and centred).
     */
    private static function detectHat(string $imagePath, array $faceBox): bool
    {
        $gd = self::loadGd($imagePath);
        if (! $gd) {
            return false;
        }

        $imgW = $faceBox['img_w'];
        $imgH = $faceBox['img_h'];
        $fx   = $faceBox['x'];
        $fy   = $faceBox['y'];
        $fw   = $faceBox['width'];
        $fh   = $faceBox['height'];

        // ── Signal 1: Hat-brim width at face-top level ─────────────────────
        $scanY     = max(0, $fy - 3);
        $leftEdge  = $fx;
        $rightEdge = $fx + $fw;

        // Scan left beyond the face box
        $leftScan = max(0, $fx - (int) ($fw * 0.6));
        for ($x = $fx - 1; $x >= $leftScan; $x--) {
            if (! self::isNearlyWhite($gd, $x, $scanY)) {
                $leftEdge = $x;
                break;
            }
        }

        // Scan right beyond the face box
        $rightScan = min($imgW - 1, $fx + $fw + (int) ($fw * 0.6));
        for ($x = $fx + $fw + 1; $x <= $rightScan; $x++) {
            if (! self::isNearlyWhite($gd, $x, $scanY)) {
                $rightEdge = $x;
            }
        }

        $nonWhiteSpanAtFaceTop = $rightEdge - $leftEdge;

        if ($nonWhiteSpanAtFaceTop > $fw * self::HAT_BRIM_RATIO) {
            imagedestroy($gd);
            Log::info('[AccessoryCheck] Hat detected via brim-width signal', [
                'non_white_span' => $nonWhiteSpanAtFaceTop,
                'face_width'     => $fw,
                'ratio'          => round($nonWhiteSpanAtFaceTop / max(1, $fw), 2),
            ]);
            return true;
        }

        // ── Signal 2: Large non-white coverage above forehead ──────────────
        // Region: y=0 to y = face_top minus a 10 % forehead margin
        $regionBottom = max(0, $fy - (int) ($fh * 0.10));

        if ($regionBottom < 10) {
            // Face is too close to the top of the image to check
            imagedestroy($gd);
            return false;
        }

        $totalPixels    = 0;
        $nonWhiteCount  = 0;
        $nonWhiteMinX   = $imgW;
        $nonWhiteMaxX   = 0;

        for ($y = 0; $y < $regionBottom; $y += 3) {
            for ($x = 0; $x < $imgW; $x += 3) {
                $totalPixels++;
                if (! self::isNearlyWhite($gd, $x, $y)) {
                    $nonWhiteCount++;
                    if ($x < $nonWhiteMinX) {
                        $nonWhiteMinX = $x;
                    }
                    if ($x > $nonWhiteMaxX) {
                        $nonWhiteMaxX = $x;
                    }
                }
            }
        }

        imagedestroy($gd);

        if ($totalPixels === 0) {
            return false;
        }

        $nonWhiteFraction = $nonWhiteCount / $totalPixels;
        $nonWhiteHorizSpan = $nonWhiteMaxX - $nonWhiteMinX;

        Log::info('[AccessoryCheck] Hat above-forehead scan', [
            'non_white_fraction'   => round($nonWhiteFraction, 3),
            'non_white_horiz_span' => $nonWhiteHorizSpan,
            'image_width'          => $imgW,
            'span_ratio'           => round($nonWhiteHorizSpan / max(1, $imgW), 2),
        ]);

        return $nonWhiteFraction > self::HAT_AREA_FRACTION
            && $nonWhiteHorizSpan > $imgW * 0.50;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────────

    private static function loadGd(string $imagePath): mixed
    {
        $ext = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));

        return match (true) {
            in_array($ext, ['jpg', 'jpeg']) => @imagecreatefromjpeg($imagePath),
            $ext === 'png'                  => @imagecreatefrompng($imagePath),
            $ext === 'webp'                 => @imagecreatefromwebp($imagePath),
            default                         => null,
        };
    }

    /** Returns true if the pixel at ($x, $y) is background-white. */
    private static function isNearlyWhite(mixed $gd, int $x, int $y): bool
    {
        $rgb = imagecolorat($gd, $x, $y);
        $r   = ($rgb >> 16) & 0xFF;
        $g   = ($rgb >> 8)  & 0xFF;
        $b   = $rgb         & 0xFF;

        return $r >= self::WHITE_THRESHOLD
            && $g >= self::WHITE_THRESHOLD
            && $b >= self::WHITE_THRESHOLD;
    }
}
