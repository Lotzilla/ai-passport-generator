<?php

namespace App\Services\Pipeline;

use App\Services\PassportRulesService;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Step 4: Smart Crop (Rule-Based)
 *
 * Centers the face within the target passport photo dimensions,
 * applying the margins specified by the country's rule set.
 */
class ImageCropService
{
    public function __construct(private PassportRulesService $rules) {}

    public function crop(string $imagePath, array $faceData, string $country): string
    {
        $rule = $this->rules->get($country);

        [$targetW, $targetH] = $rule['dimensions_px'];    // e.g. [600, 600] for US 2x2 at 300dpi
        $faceHeightTarget = ($rule['face_height_percent'][0] + $rule['face_height_percent'][1]) / 2;

        $manager = new ImageManager(new Driver());
        $image   = $manager->read($imagePath);

        // --- Calculate crop region so face fills faceHeightTarget of the output ---
        $faceH       = $faceData['height'];
        $faceY       = $faceData['y'];
        $faceCenterX = $faceData['x'] + $faceData['width'] / 2;

        // Desired crop height based on face
        $desiredCropH = (int) ($faceH / $faceHeightTarget);
        $desiredCropW = (int) ($desiredCropH * $targetW / $targetH);

        // Vertical: place face so it starts at ~15% from top of crop
        $cropY = (int) ($faceY - $desiredCropH * 0.15);
        $cropY = max(0, $cropY);

        // Horizontal: centre on face
        $cropX = (int) ($faceCenterX - $desiredCropW / 2);
        $cropX = max(0, $cropX);

        // Clamp to image boundaries
        $imgW    = $faceData['img_w'];
        $imgH    = $faceData['img_h'];
        $cropX   = min($cropX, $imgW - $desiredCropW);
        $cropY   = min($cropY, $imgH - $desiredCropH);
        $cropX   = max(0, $cropX);
        $cropY   = max(0, $cropY);

        $image->crop($desiredCropW, $desiredCropH, $cropX, $cropY);
        $image->resize($targetW, $targetH);

        $outputPath = storage_path('app/tmp/' . Str::uuid() . '.png');
        $image->toPng()->save($outputPath);

        return $outputPath;
    }
}
