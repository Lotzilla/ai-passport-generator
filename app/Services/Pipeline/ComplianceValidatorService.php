<?php

namespace App\Services\Pipeline;

use App\Services\PassportRulesService;

/**
 * Step 6: Compliance Validation Engine (Rule-Based — NO AI)
 *
 * Validates:
 *  - Face detected
 *  - Face size within allowed percentage range
 *  - Face horizontally centred
 *  - Background is sufficiently white/plain (corner sampling)
 */
class ComplianceValidatorService
{
    public function __construct(private PassportRulesService $rules) {}

    /**
     * @return array{ pass: bool, errors: string[] }
     */
    public function validate(string $imagePath, array $faceData, string $country): array
    {
        $rule   = $this->rules->get($country);
        $errors = [];

        [$imgW, $imgH] = getimagesize($imagePath);

        // 1. Face detected
        if (! $faceData['detected']) {
            $errors[] = 'No face detected in the photo.';
            return ['pass' => false, 'errors' => $errors];
        }

        // 2. Face height percentage
        $faceHeightPct = $faceData['height'] / $faceData['img_h'];
        [$minPct, $maxPct] = $rule['face_height_percent'];

        if ($faceHeightPct < $minPct) {
            $errors[] = sprintf(
                'Face is too small (%.0f%%). Move closer to the camera.',
                $faceHeightPct * 100
            );
        } elseif ($faceHeightPct > $maxPct) {
            $errors[] = sprintf(
                'Face is too large (%.0f%%). Move further from the camera.',
                $faceHeightPct * 100
            );
        }

        // 3. Face horizontally centred (centre of face within ±15% of image centre)
        $faceCentreX   = $faceData['x'] + $faceData['width'] / 2;
        $imageCentreX  = $faceData['img_w'] / 2;
        $offsetPercent = abs($faceCentreX - $imageCentreX) / $faceData['img_w'];

        if ($offsetPercent > 0.15) {
            $errors[] = 'Face is not centred horizontally. Please look straight at the camera.';
        }

        // 4. Background whiteness (corner sampling)
        if (! $this->isBackgroundPlain($imagePath)) {
            $errors[] = 'Background does not appear to be plain white. Please use a white wall or blank background.';
        }

        return [
            'pass'   => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Sample the four corners of the processed image.
     * Each corner should average > 220/255 brightness.
     */
    private function isBackgroundPlain(string $imagePath): bool
    {
        [$w, $h] = getimagesize($imagePath);

        $gdImage = match (true) {
            str_ends_with(strtolower($imagePath), '.jpg'),
            str_ends_with(strtolower($imagePath), '.jpeg') => imagecreatefromjpeg($imagePath),
            default => imagecreatefrompng($imagePath),
        };

        $sampleSize  = (int) min(30, $w * 0.05, $h * 0.05);
        $cornerBrightness = [];

        $corners = [
            [0, 0],
            [$w - $sampleSize, 0],
            [0, $h - $sampleSize],
            [$w - $sampleSize, $h - $sampleSize],
        ];

        foreach ($corners as [$cx, $cy]) {
            $total = 0;
            $count = 0;
            for ($x = $cx; $x < $cx + $sampleSize && $x < $w; $x++) {
                for ($y = $cy; $y < $cy + $sampleSize && $y < $h; $y++) {
                    $rgb    = imagecolorat($gdImage, $x, $y);
                    $r      = ($rgb >> 16) & 0xFF;
                    $g      = ($rgb >> 8) & 0xFF;
                    $b      = $rgb & 0xFF;
                    $total += (0.299 * $r + 0.587 * $g + 0.114 * $b);
                    $count++;
                }
            }
            $cornerBrightness[] = $count > 0 ? $total / $count : 255;
        }

        

        // At least 3 of 4 corners must be bright
        $brightCorners = count(array_filter($cornerBrightness, fn ($b) => $b >= 200));
        return $brightCorners >= 3;
    }
}
