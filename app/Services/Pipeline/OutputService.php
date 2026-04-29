<?php

namespace App\Services\Pipeline;

use Illuminate\Support\Str;

/**
 * Step 7: Output
 *
 * Moves the final processed image into /storage/processed/.
 */
class OutputService
{
    public function save(string $tempPath): string
    {
        $outputDir = storage_path('app/processed');
        @mkdir($outputDir, 0755, true);

        $filename   = 'passport-' . Str::uuid() . '.png';
        $outputPath = $outputDir . '/' . $filename;

        rename($tempPath, $outputPath);

        return $filename;
    }
}
