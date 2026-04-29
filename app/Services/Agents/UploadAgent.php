<?php

namespace App\Services\Agents;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Agent 1 — Upload Agent
 *
 * Responsibility: Validate, store the uploaded file, and initialise the shared payload.
 *
 * Input : Illuminate\Http\Request (file: 'image', country: 'US')
 * Output: array $payload (shared data contract)
 */
class UploadAgent
{
    public static function handle(Request $request): array
    {
        $file     = $request->file('image');
        $country  = strtoupper($request->input('country', 'US'));
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

        $storagePath = storage_path('app/uploads');
        @mkdir($storagePath, 0755, true);

        $file->move($storagePath, $filename);

        $fullPath = $storagePath . DIRECTORY_SEPARATOR . $filename;

        return [
            'originalImage'      => $fullPath,
            'processedImage'     => $fullPath,   // starts as the original; agents overwrite with their output
            'faceBox'            => null,
            'faceDetected'       => false,
            'backgroundRemoved'  => false,
            'country'            => $country,
            'errors'             => [],
            'status'             => 'processing',
            'outputFilename'     => null,
            'downloadUrl'        => null,
            'message'            => null,
        ];
    }
}
