<?php

namespace App\Services\Agents;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Agent 5 — Lighting Agent
 *
 * Responsibility: Normalise brightness, fix shadows, and boost contrast so
 *                 the face looks naturally well-lit in the final passport photo —
 *                 regardless of the original lighting conditions.
 *
 * Algorithm:
 *  1. Sample the face region (centre strip) to measure average luminance,
 *     skipping white background pixels that BackgroundRemovalAgent created.
 *  2. Compute a gamma value that maps the face average to the target.
 *     Gamma correction is non-linear: it lifts dark shadows strongly while
 *     barely changing bright highlights — far more effective than a flat
 *     brightness offset for real-world selfies and indoor shots.
 *  3. Apply gamma via PHP GD's imagegammacorrect() — very fast (no per-pixel
 *     loop) and preserves pure white background exactly.
 *  4. Apply a moderate contrast boost via imagefilter(IMG_FILTER_CONTRAST).
 *
 * Input  payload keys: processedImage
 * Output payload keys: processedImage (path to enhanced image)
 */
class LightingAgent
{
    /**
     * Target luminance for the face area (0–255).
     * 148 = slightly above mid-grey → natural, passport-ready brightness.
     */
    private const FACE_TARGET = 148;

    /** Hard limits on the computed gamma to prevent over/under exposure. */
    private const GAMMA_MIN = 0.40;  // max brightening (very dark rooms, night selfies)
    private const GAMMA_MAX = 2.20;  // max darkening  (overexposed outdoor shots)

    /**
     * GD contrast filter value. In GD, negative = MORE contrast.
     * -18 gives a visible but natural-looking contrast lift.
     */
    private const CONTRAST_BOOST = -18;

    public static function handle(array $payload): array
    {
        $imgPath = $payload['processedImage'];
        $ext     = strtolower(pathinfo($imgPath, PATHINFO_EXTENSION));
        $gd      = in_array($ext, ['jpg', 'jpeg'])
            ? imagecreatefromjpeg($imgPath)
            : imagecreatefrompng($imgPath);

        $w = imagesx($gd);
        $h = imagesy($gd);

        // ── 1. Sample face-region luminance (skip white background) ───────
        $avg = self::sampleFaceLuminance($gd, $w, $h);

        Log::info('[Lighting] Face luminance sampled', ['avg' => round($avg, 1), 'target' => self::FACE_TARGET]);

        // ── 2. Compute gamma ───────────────────────────────────────────────
        // Solve:  target/255 = (avg/255)^gamma  →  gamma = log(t/255)/log(a/255)
        // imagegammacorrect($gd, 1.0, gamma) applies:  out = 255*(in/255)^(gamma/1.0)
        $gamma = self::computeGamma($avg, self::FACE_TARGET);
        $gamma = max(self::GAMMA_MIN, min(self::GAMMA_MAX, $gamma));

        Log::info('[Lighting] Applying gamma correction', ['gamma' => round($gamma, 3)]);

        // ── 3. Apply gamma (fast GD built-in — no per-pixel loop needed) ──
        // imagegammacorrect($img, $input, $output) applies: out = 255*(in/255)^(input/output)
        // To brighten (power < 1): pass gamma as INPUT, 1.0 as OUTPUT → power = gamma < 1 ✓
        if (abs(1.0 - $gamma) > 0.04) {
            imagegammacorrect($gd, $gamma, 1.0);
        }

        // ── 4. Contrast boost ──────────────────────────────────────────────
        imagefilter($gd, IMG_FILTER_CONTRAST, self::CONTRAST_BOOST);

        // ── Save ───────────────────────────────────────────────────────────
        $outPath = storage_path('app/tmp/' . Str::uuid() . '.png');
        imagepng($gd, $outPath);
        unset($gd);

        $payload['processedImage'] = $outPath;

        return $payload;
    }

    /**
     * Sample average luminance of the face region.
     *
     * Only the upper 55 % of the image height is sampled — this targets the
     * face and avoids dark clothing / accessories (headphones, jackets) in
     * the lower portion skewing the measurement and causing over-brightening.
     * Pure-white background pixels are excluded.
     */
    private static function sampleFaceLuminance($gd, int $w, int $h): float
    {
        $x1   = (int) ($w * 0.20);
        $x2   = (int) ($w * 0.80);
        $y1   = (int) ($h * 0.10);
        $y2   = (int) ($h * 0.55);   // stop above chest/clothing area
        $step = max(1, (int) (($x2 - $x1) / 30));

        $total = $count = 0;

        for ($y = $y1; $y < $y2; $y += $step) {
            for ($x = $x1; $x < $x2; $x += $step) {
                $rgb = imagecolorat($gd, $x, $y);
                $r   = ($rgb >> 16) & 0xFF;
                $g   = ($rgb >> 8)  & 0xFF;
                $b   = $rgb         & 0xFF;

                // Skip near-white background pixels
                if ($r > 240 && $g > 240 && $b > 240) {
                    continue;
                }

                $total += 0.299 * $r + 0.587 * $g + 0.114 * $b;
                $count++;
            }
        }

        return $count > 0 ? $total / $count : self::FACE_TARGET;
    }

    /**
     * Compute the gamma value that maps $avg → $target luminance.
     *
     * Formula derivation:
     *   target/255 = (avg/255)^gamma
     *   gamma = log(target/255) / log(avg/255)
     */
    private static function computeGamma(float $avg, float $target): float
    {
        if ($avg <= 1.0) {
            return self::GAMMA_MIN;
        }

        if (abs($avg - $target) < 3) {
            return 1.0; // already at target — no correction needed
        }

        return log($target / 255.0) / log($avg / 255.0);
    }
}
