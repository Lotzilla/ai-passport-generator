<?php

namespace App\Services\Pipeline;

use GuzzleHttp\Client;
use Illuminate\Support\Str;

/**
 * Step 2: Background Removal
 *
 * Uses remove.bg API (or falls back to a white background simulation for dev).
 * Set REMOVE_BG_API_KEY in .env to enable the real service.
 */
class BackgroundRemovalService
{
    private string $apiKey;
    private string $apiUrl = 'https://api.remove.bg/v1.0/removebg';

    public function __construct()
    {
        $this->apiKey = config('services.removebg.key', '');
    }

    public function process(string $sourcePath): string
    {
        $outputPath = storage_path('app/tmp/' . Str::uuid() . '.png');
        @mkdir(dirname($outputPath), 0755, true);

        if (empty($this->apiKey) || $this->apiKey === 'sandbox') {
            // Dev fallback: copy original and composite a white background
            $this->applyWhiteBackground($sourcePath, $outputPath);
            return $outputPath;
        }

        $client = new Client(['timeout' => 30]);

        $response = $client->post($this->apiUrl, [
            'headers' => [
                'X-Api-Key' => $this->apiKey,
            ],
            'multipart' => [
                [
                    'name'     => 'image_file',
                    'contents' => fopen($sourcePath, 'r'),
                ],
                ['name' => 'size', 'contents' => 'auto'],
                ['name' => 'bg_color', 'contents' => 'ffffff'],
            ],
            'sink' => $outputPath,
        ]);

        return $outputPath;
    }

    /**
     * Dev fallback: use Intervention Image to place photo on white canvas.
     */
    private function applyWhiteBackground(string $sourcePath, string $outputPath): void
    {
        $manager = new \Intervention\Image\ImageManager(
            new \Intervention\Image\Drivers\Gd\Driver()
        );

        $image  = $manager->read($sourcePath);
        $width  = $image->width();
        $height = $image->height();

        $canvas = $manager->create($width, $height)->fill('ffffff');
        $canvas->place($image, 'center');
        $canvas->toPng()->save($outputPath);
    }
}
