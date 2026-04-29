<?php

namespace App\Services\Pipeline;

use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Step 5: Lighting Adjustment
 *
 * Applies gentle brightness/contrast normalisation.
 * Keeps skin tones natural — no heavy filtering.
 */
class LightingAdjustmentService
{
    // Adjustment caps: keep changes conservative
    private const BRIGHTNESS_TARGET = 128;   // mid-grey ideal
    private const MAX_BRIGHTNESS_ADJUST = 20; // ±20 out of 255
    private const CONTRAST_ADJUST = 5;        // slight contrast lift

    public function adjust(string $imagePath): string
    {
        $manager = new ImageManager(new Driver());
        $image   = $manager->read($imagePath);

        // Sample average brightness from the face area (centre 50%)
        $avgBrightness = $this->sampleBrightness($imagePath);

        $delta = self::BRIGHTNESS_TARGET - $avgBrightness;
        // Clamp delta
        $delta = max(-self::MAX_BRIGHTNESS_ADJUST, min(self::MAX_BRIGHTNESS_ADJUST, $delta));

        if (abs($delta) > 3) {
            $image->brightness((int) $delta);
        }

        $image->contrast(self::CONTRAST_ADJUST);

        $outputPath = storage_path('app/tmp/' . Str::uuid() . '.png');
        $image->toPng()->save($outputPath);

        return $outputPath;
    }

    private function sampleBrightness(string $imagePath): float
    {
        [$w, $h] = getimagesize($imagePath);

        $gdImage = match (true) {
            str_ends_with(strtolower($imagePath), '.jpg'),
            str_ends_with(strtolower($imagePath), '.jpeg') => imagecreatefromjpeg($imagePath),
            default => imagecreatefrompng($imagePath),
        };

        $total  = 0;
        $count  = 0;
        $step   = max(1, (int) ($w / 20)); // sample ~20 points per row

        $x1 = (int) ($w * 0.25);
        $x2 = (int) ($w * 0.75);
        $y1 = (int) ($h * 0.10);
        $y2 = (int) ($h * 0.80);

        for ($y = $y1; $y < $y2; $y += $step) {
            for ($x = $x1; $x < $x2; $x += $step) {
                $rgb    = imagecolorat($gdImage, $x, $y);
                $r      = ($rgb >> 16) & 0xFF;
                $g      = ($rgb >> 8) & 0xFF;
                $b      = $rgb & 0xFF;
                $total += (0.299 * $r + 0.587 * $g + 0.114 * $b);
                $count++;
            }
        }

        
        return $count > 0 ? $total / $count : self::BRIGHTNESS_TARGET;
    }
}
