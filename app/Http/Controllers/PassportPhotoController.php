<?php

namespace App\Http\Controllers;

use App\Services\Pipeline\PassportPipeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PassportPhotoController extends Controller
{
    public function __construct(private PassportPipeline $pipeline) {}

    /**
     * Single endpoint: upload + full pipeline + response.
     *
     * POST /process-photo
     * Body: multipart/form-data { image: file, country: string }
     */
    public function process(Request $request): JsonResponse
    {
        $request->validate([
            'image'   => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'country' => ['sometimes', 'string', 'size:2'],
        ]);

        try {
            $result = $this->pipeline->run($request);

            return response()->json([
                'success'     => $result['status'] === 'success',
                'message'     => $result['message'],
                'downloadUrl' => $result['downloadUrl'],
                'errors'      => $result['errors'],
            ]);
        } catch (\Throwable $e) {
            report($e);

            $debug = config('app.debug')
                ? [
                    'exception' => get_class($e),
                    'message'   => $e->getMessage(),
                    'file'      => $e->getFile(),
                    'line'      => $e->getLine(),
                    'trace'     => array_slice(
                        array_map(
                            fn ($f) => ($f['file'] ?? '?') . ':' . ($f['line'] ?? '?') . ' ' . ($f['class'] ?? '') . ($f['type'] ?? '') . ($f['function'] ?? ''),
                            $e->getTrace()
                        ),
                        0,
                        10
                    ),
                ]
                : null;

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again.',
                'errors'  => [],
                'debug'   => $debug,
            ], 500);
        }
    }

    /**
     * GET /download-photo/{filename}
     */
    public function download(string $filename): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $filename = basename($filename);
        $filePath = storage_path("app/processed/{$filename}");

        abort_unless(file_exists($filePath), 404, 'File not found.');

        // Build a clean, descriptive download name from the stored filename.
        // Stored format: passport-{COUNTRY}-{W}x{H}-{uuid}.png
        // Download name: passport-photo-{COUNTRY}-{W}x{H}px.png
        if (preg_match('/^passport-([A-Z]{2})-([0-9]+x[0-9]+)-[a-f0-9\-]+\.png$/i', $filename, $m)) {
            $downloadName = "passport-photo-{$m[1]}-{$m[2]}px.png";
        } else {
            $downloadName = "passport-photo-{$filename}";
        }

        return response()->download($filePath, $downloadName);
    }
}
