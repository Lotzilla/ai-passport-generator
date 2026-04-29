<?php

namespace App\Services\Pipeline;

/**
 * Step 3: Face Detection
 *
 * Uses the face-api.js approach server-side via a Python micro-service OR
 * a fallback heuristic (centre-of-image) for dev/MVP.
 *
 * Set FACE_API_URL in .env to enable a real face-detection micro-service.
 * The micro-service should accept a POST with image_path and return JSON:
 *   { detected: true, x, y, width, height, eye_y }
 */
class FaceDetectionService
{
    private string $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.face_detection.url', '');
    }

    /**
     * Returns:
     *   detected  bool
     *   x, y      top-left of bounding box (pixels)
     *   width     bounding box width
     *   height    bounding box height
     *   eye_y     approximate eye-line y coordinate
     *   img_w     source image width
     *   img_h     source image height
     */
    public function detect(string $imagePath): array
    {
        if (! empty($this->apiUrl)) {
            return $this->remoteDetect($imagePath);
        }

        return $this->heuristicDetect($imagePath);
    }

    private function remoteDetect(string $imagePath): array
    {
        $client = new \GuzzleHttp\Client(['timeout' => 15]);

        $response = $client->post($this->apiUrl, [
            'multipart' => [
                [
                    'name'     => 'image',
                    'contents' => fopen($imagePath, 'r'),
                ],
            ],
        ]);

        $data = json_decode((string) $response->getBody(), true);
        return $data ?? ['detected' => false];
    }

    /**
     * Fallback heuristic: assume face occupies the upper-centre 60% of image.
     * This is good enough for MVP demo without a real face-detection service.
     */
    private function heuristicDetect(string $imagePath): array
    {
        [$imgW, $imgH] = getimagesize($imagePath);

        $faceW = (int) ($imgW * 0.5);
        $faceH = (int) ($imgH * 0.55);
        $x     = (int) (($imgW - $faceW) / 2);
        $y     = (int) ($imgH * 0.05);

        return [
            'detected' => true,
            'x'        => $x,
            'y'        => $y,
            'width'    => $faceW,
            'height'   => $faceH,
            'eye_y'    => (int) ($y + $faceH * 0.35),
            'img_w'    => $imgW,
            'img_h'    => $imgH,
        ];
    }
}
