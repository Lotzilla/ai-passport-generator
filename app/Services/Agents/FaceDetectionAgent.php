<?php

namespace App\Services\Agents;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * Agent 3 — Face Detection Agent
 *
 * Responsibility: Detect face bounding box and measurements.
 *
 * Input  payload keys: processedImage
 * Output payload keys: faceDetected (bool), faceBox (array|null)
 *
 * faceBox shape:
 *   { x, y, width, height, eye_y, img_w, img_h }
 *
 * Priority order:
 *   1. Incode Omni API  — POST /omni/start then /omni/add/front-id/v2
 *   2. Custom skin-tone heat-map (no external dependencies)
 *   3. Geometric heuristic fallback
 */
class FaceDetectionAgent
{
    private const INCODE_BASE_URL = 'https://demo-api.incodesmile.com';

    // Skin tone HSV ranges (hue 0-360, sat/val 0-100)
    private const SKIN_HUE_MIN = 0;
    private const SKIN_HUE_MAX = 35;
    private const SKIN_SAT_MIN = 15;
    private const SKIN_SAT_MAX = 80;
    private const SKIN_VAL_MIN = 30;
    private const SKIN_VAL_MAX = 100;

    // Grid resolution for the scan
    private const GRID_COLS = 20;
    private const GRID_ROWS = 24;

    public static function handle(array $payload): array
    {
        $imagePath    = $payload['processedImage'] ?? $payload['originalImage'];
        $pythonApiUrl = config('services.ai_pipeline.url', '');
        $useIncode    = config('services.incode.enabled', true);

        // Tier 0: Local Python AI service (MediaPipe) — highest accuracy
        if (! empty($pythonApiUrl)) {
            $faceData = self::pythonDetect($imagePath, $pythonApiUrl);
        } elseif ($useIncode) {
            $faceData = self::incodeDetect($imagePath);
        } else {
            $faceData = self::skinToneDetect($imagePath);
        }

        $payload['faceDetected'] = $faceData['detected'];
        $payload['faceBox']      = $faceData['detected'] ? [
            'x'      => $faceData['x'],
            'y'      => $faceData['y'],
            'width'  => $faceData['width'],
            'height' => $faceData['height'],
            'eye_y'  => $faceData['eye_y'],
            'img_w'  => $faceData['img_w'],
            'img_h'  => $faceData['img_h'],
        ] : null;

        // Store MediaPipe landmarks in payload if available (e.g. for future agents)
        if (! empty($faceData['landmarks'])) {
            $payload['faceLandmarks'] = $faceData['landmarks'];
        }

        return $payload;
    }

    // ── Python AI service (MediaPipe) ──────────────────────────────────────────

    private static function pythonDetect(string $imagePath, string $baseUrl): array
    {
        try {
            $client   = new Client(['timeout' => 15]);
            $response = $client->post(rtrim($baseUrl, '/') . '/detect-face', [
                'multipart' => [
                    ['name' => 'image', 'contents' => fopen($imagePath, 'r')],
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);

            if (empty($body['faceDetected'])) {
                return self::buildEmptyFace(getimagesize($imagePath));
            }

            $fb   = $body['faceBox'];
            $lm   = $body['landmarks'] ?? [];

            // eye_y = midpoint of the two eye centres
            $eyeY = $fb['y'] + (int) ($fb['height'] * 0.35);
            if (! empty($lm['leftEye']) && ! empty($lm['rightEye'])) {
                $eyeY = (int) (($lm['leftEye'][1] + $lm['rightEye'][1]) / 2);
            }

            return [
                'detected'  => true,
                'x'         => (int) $fb['x'],
                'y'         => (int) $fb['y'],
                'width'     => (int) $fb['width'],
                'height'    => (int) $fb['height'],
                'eye_y'     => $eyeY,
                'img_w'     => (int) $fb['img_w'],
                'img_h'     => (int) $fb['img_h'],
                'landmarks' => $lm,
            ];

        } catch (\Throwable $e) {
            Log::warning('FaceDetectionAgent: Python service unavailable, falling back.', [
                'error' => $e->getMessage(),
            ]);
            return self::skinToneDetect($imagePath);
        }
    }

    private static function buildEmptyFace(array $size): array
    {
        return [
            'detected' => false,
            'x' => 0, 'y' => 0, 'width' => 0, 'height' => 0,
            'eye_y' => 0, 'img_w' => $size[0] ?? 0, 'img_h' => $size[1] ?? 0,
        ];
    }

    // ── Incode Omni API ────────────────────────────────────────────────────────

    /**
     * Step 1 — create an Incode Omni session.
     * Returns the session token string, or null on failure.
     */
    private static function startIncodeSession(Client $client): ?string
    {
        $apiKey  = config('services.incode.api_key', '');
        $headers = ['Content-Type' => 'application/json'];

        if (! empty($apiKey)) {
            $headers['api-key'] = $apiKey;
        }

        $response = $client->post(self::INCODE_BASE_URL . '/omni/start', [
            'headers' => $headers,
            'json'    => [
                'countryCode' => 'ALL',
                'language'    => 'en-US',
            ],
        ]);

        $body = json_decode((string) $response->getBody(), true);

        return $body['token'] ?? null;
    }

    /**
     * Step 2 — submit the image to /omni/add/front-id/v2 and extract
     * the face bounding box from the response.
     */
    private static function incodeDetect(string $imagePath): array
    {
        try {
            [$imgW, $imgH] = getimagesize($imagePath);

            $client = new Client([
                'timeout'         => 20,
                'connect_timeout' => 10,
            ]);

            $token = self::startIncodeSession($client);

            if (! $token) {
                Log::warning('FaceDetectionAgent: Incode session token was empty, using skin-tone fallback.');
                return self::skinToneDetect($imagePath);
            }

            $response = $client->post(self::INCODE_BASE_URL . '/omni/add/front-id/v2', [
                'headers'   => [
                    'X-Incode-Hardware-Id' => $token,
                ],
                'multipart' => [
                    [
                        'name'     => 'front',
                        'contents' => fopen($imagePath, 'r'),
                        'filename' => 'photo.jpg',
                    ],
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true) ?? [];

            return self::parseIncodeResponse($body, $imgW, $imgH);

        } catch (\Throwable $e) {
            Log::warning('FaceDetectionAgent: Incode API failed, using skin-tone fallback.', [
                'error' => $e->getMessage(),
            ]);

            return self::skinToneDetect($imagePath);
        }
    }

    /**
     * Extract face bounding box from the Incode /front-id/v2 response.
     * Tries common field names in priority order.
     * Incode may return coords as fractions (0-1) or pixels — we handle both.
     */
    private static function parseIncodeResponse(array $body, int $imgW, int $imgH): array
    {
        $box = $body['faceBox']
            ?? $body['cropCoords']
            ?? $body['face']
            ?? $body['boundingBox']
            ?? null;

        if (! $box) {
            Log::info('FaceDetectionAgent: No face box in Incode response, using skin-tone fallback.', [
                'response_keys' => array_keys($body),
            ]);
            return self::skinToneDetect($imagePath ?? '');
        }

        // Incode returns fractions when values are <= 2.0, pixels otherwise
        $isFractional = ($box['width'] ?? 0) <= 2.0;

        $x = $isFractional
            ? (int) round(($box['x'] ?? $box['left'] ?? 0) * $imgW)
            : (int) round($box['x']  ?? $box['left'] ?? 0);

        $y = $isFractional
            ? (int) round(($box['y'] ?? $box['top'] ?? 0) * $imgH)
            : (int) round($box['y']  ?? $box['top'] ?? 0);

        $w = $isFractional
            ? (int) round(($box['width']  ?? 0.5) * $imgW)
            : (int) round($box['width']   ?? $imgW * 0.5);

        $h = $isFractional
            ? (int) round(($box['height'] ?? 0.55) * $imgH)
            : (int) round($box['height']  ?? $imgH * 0.55);

        if ($w < 10 || $h < 10) {
            return self::buildEmptyFace([$imgW, $imgH]);
        }

        return [
            'detected' => true,
            'x'        => $x,
            'y'        => $y,
            'width'    => $w,
            'height'   => $h,
            'eye_y'    => (int) ($y + $h * 0.35),
            'img_w'    => $imgW,
            'img_h'    => $imgH,
        ];
    }

    // ── Custom skin-tone heat-map detector ─────────────────────────────────────

    /**
     * Face detection using a skin-tone heat-map on a coarse grid.
     * Zero external dependencies — pure PHP GD.
     *
     * 1. Downsample image to GRID_COLS x GRID_ROWS cells.
     * 2. Score each cell as skin-like using YCbCr + HSV ranges.
     * 3. Flood-fill the largest connected skin cluster.
     * 4. Map cluster bounding box back to pixel coordinates.
     */
    private static function skinToneDetect(string $imagePath): array
    {
        [$imgW, $imgH] = getimagesize($imagePath);
        $ext           = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));

        $gd = match (true) {
            in_array($ext, ['jpg', 'jpeg']) => imagecreatefromjpeg($imagePath),
            $ext === 'png'                  => imagecreatefrompng($imagePath),
            default                         => false,
        };

        if (! $gd) {
            Log::warning('FaceDetectionAgent: Could not load image for skin-tone detection.');
            return self::buildEmptyFace([$imgW, $imgH]);
        }

        $cellW = $imgW / self::GRID_COLS;
        $cellH = $imgH / self::GRID_ROWS;

        $grid = [];
        for ($row = 0; $row < self::GRID_ROWS; $row++) {
            for ($col = 0; $col < self::GRID_COLS; $col++) {
                $px  = (int) ($col * $cellW + $cellW / 2);
                $py  = (int) ($row * $cellH + $cellH / 2);
                $rgb = imagecolorat($gd, min($px, $imgW - 1), min($py, $imgH - 1));

                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8)  & 0xFF;
                $b =  $rgb        & 0xFF;

                $grid[$row][$col] = self::isSkinTone($r, $g, $b) ? 1 : 0;
            }
        }

        $bestScore = -1;
        $seedRow   = -1;
        $seedCol   = -1;
        $upperRows = (int) (self::GRID_ROWS * 0.70);

        // Pass 1 — centre 50 % of columns only.
        // Edge objects (curtains, plants, coloured walls) that pass the
        // skin-tone filter are almost always at the horizontal extremes.
        // Restricting the seed search to the centre keeps them out.
        $colMargin = (int) round(self::GRID_COLS * 0.25); // 25 % from each side
        for ($row = 0; $row < $upperRows; $row++) {
            for ($col = $colMargin; $col < self::GRID_COLS - $colMargin; $col++) {
                if (! $grid[$row][$col]) {
                    continue;
                }
                $score = self::neighbourScore($grid, $row, $col);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $seedRow   = $row;
                    $seedCol   = $col;
                }
            }
        }

        // Pass 2 — full width fallback if no centred dense cluster found.
        if ($bestScore < 3) {
            for ($row = 0; $row < $upperRows; $row++) {
                for ($col = 0; $col < self::GRID_COLS; $col++) {
                    if (! $grid[$row][$col]) {
                        continue;
                    }
                    $score = self::neighbourScore($grid, $row, $col);
                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $seedRow   = $row;
                        $seedCol   = $col;
                    }
                }
            }
        }

        // Require a dense skin cluster around the seed.
        if ($seedRow === -1 || $bestScore < 3) {
            return self::buildEmptyFace([$imgW, $imgH]);
        }

        $visited = [];
        $cluster = [];
        $queue   = [[$seedRow, $seedCol]];
        $visited[$seedRow][$seedCol] = true;

        while (! empty($queue)) {
            [$r, $c] = array_shift($queue);
            $cluster[] = [$r, $c];

            foreach ([[-1, 0], [1, 0], [0, -1], [0, 1]] as [$dr, $dc]) {
                $nr = $r + $dr;
                $nc = $c + $dc;
                if ($nr >= 0 && $nr < self::GRID_ROWS &&
                    $nc >= 0 && $nc < self::GRID_COLS &&
                    ! isset($visited[$nr][$nc]) &&
                    ($grid[$nr][$nc] ?? 0)) {
                    $visited[$nr][$nc] = true;
                    $queue[]           = [$nr, $nc];
                }
            }
        }

        // A real face must occupy at least 4% of the grid (≈19 cells on a 20×24
        // grid). 4 cells is < 1% — any warm-coloured object passes that.
        $minCells = (int) (self::GRID_COLS * self::GRID_ROWS * 0.04);
        if (count($cluster) < $minCells) {
            return self::buildEmptyFace([$imgW, $imgH]);
        }

        $minRow = min(array_column($cluster, 0));
        $maxRow = max(array_column($cluster, 0));
        $minCol = min(array_column($cluster, 1));
        $maxCol = max(array_column($cluster, 1));

        $pad = 0.08;
        $x   = max(0,     (int) (($minCol - $pad * self::GRID_COLS) * $cellW));
        $y   = max(0,     (int) (($minRow - $pad * self::GRID_ROWS) * $cellH));
        $x2  = min($imgW, (int) (($maxCol + 1 + $pad * self::GRID_COLS) * $cellW));
        $y2  = min($imgH, (int) (($maxRow + 1 + $pad * self::GRID_ROWS) * $cellH));

        $w = $x2 - $x;
        $h = $y2 - $y;

        // Geometry sanity — reject false positives from warm-coloured walls,
        // floors, or furniture that happen to pass the skin-tone filter.
        $centroidRow = array_sum(array_column($cluster, 0)) / count($cluster);
        $centroidCol = array_sum(array_column($cluster, 1)) / count($cluster);
        $aspectRatio = ($h > 0) ? ($w / $h) : 999;

        if (
            $w < 10 || $h < 10
            || $h < ($imgH * 0.06)                          // face < 6% of image height — too small
            || $aspectRatio > 3.0                           // more than 3× wider than tall — not a face
            || $centroidRow > (self::GRID_ROWS * 0.85)      // cluster centre in bottom 15% — not a face
            || $centroidCol < (self::GRID_COLS * 0.10)      // cluster entirely on left edge
            || $centroidCol > (self::GRID_COLS * 0.90)      // cluster entirely on right edge
        ) {
            Log::info('FaceDetectionAgent: Skin cluster found but failed geometry checks — treating as no face.', [
                'cluster_cells' => count($cluster),
                'box_w'         => $w,
                'box_h'         => $h,
                'aspect_ratio'  => round($aspectRatio, 2),
                'centroid_row'  => round($centroidRow, 1),
            ]);
            return self::buildEmptyFace([$imgW, $imgH]);
        }

        return [
            'detected' => true,
            'x'        => $x,
            'y'        => $y,
            'width'    => $w,
            'height'   => $h,
            'eye_y'    => (int) ($y + $h * 0.35),
            'img_w'    => $imgW,
            'img_h'    => $imgH,
        ];
    }

    /**
     * Check whether an RGB pixel falls within human skin-tone ranges.
     * Uses YCbCr (cross-ethnicity) + HSV for edge-case coverage.
     */
    private static function isSkinTone(int $r, int $g, int $b): bool
    {
        $y  =  0.299   * $r + 0.587   * $g + 0.114   * $b;
        $cb = -0.16874 * $r - 0.33126 * $g + 0.5     * $b + 128;
        $cr =  0.5     * $r - 0.41869 * $g - 0.08131 * $b + 128;

        $ycbcr = ($y > 80) && ($cb >= 77 && $cb <= 127) && ($cr >= 133 && $cr <= 173);

        $rf    = $r / 255.0;
        $gf    = $g / 255.0;
        $bf    = $b / 255.0;
        $max   = max($rf, $gf, $bf);
        $min   = min($rf, $gf, $bf);
        $delta = $max - $min;
        $val   = $max * 100;
        $sat   = $max > 0 ? ($delta / $max) * 100 : 0;

        if ($delta == 0) {
            $hue = 0.0;
        } elseif ($max == $rf) {
            $hue = fmod(60 * (($gf - $bf) / $delta) + 360, 360);
        } elseif ($max == $gf) {
            $hue = 60 * (($bf - $rf) / $delta) + 120;
        } else {
            $hue = 60 * (($rf - $gf) / $delta) + 240;
        }

        $hsv = ($hue >= self::SKIN_HUE_MIN && $hue <= self::SKIN_HUE_MAX)
            && ($sat >= self::SKIN_SAT_MIN  && $sat <= self::SKIN_SAT_MAX)
            && ($val >= self::SKIN_VAL_MIN  && $val <= self::SKIN_VAL_MAX);

        return $ycbcr || $hsv;
    }

    private static function neighbourScore(array $grid, int $row, int $col): int
    {
        $score = 0;
        for ($dr = -1; $dr <= 1; $dr++) {
            for ($dc = -1; $dc <= 1; $dc++) {
                if ($dr === 0 && $dc === 0) {
                    continue;
                }
                $score += $grid[$row + $dr][$col + $dc] ?? 0;
            }
        }

        return $score;
    }

    /**
     * No face could be detected — return a negative result.
     * Previously this guessed a box, but that caused photos with no face to
     * pass the detection gate. Now it correctly signals failure.
     */
    private static function heuristicBox(int $imgW, int $imgH): array
    {
        return [
            'detected' => false,
            'x'        => 0,
            'y'        => 0,
            'width'    => 0,
            'height'   => 0,
            'eye_y'    => 0,
            'img_w'    => $imgW,
            'img_h'    => $imgH,
        ];
    }
}
