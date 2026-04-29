<?php

namespace App\Services\Agents;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Agent 2 — Background Removal Agent
 *
 * Responsibility: Remove background and replace with plain white — regardless
 *                 of what is in the background.
 *
 * Priority order:
 *   1. remove.bg API          — REMOVE_BG_API_KEY set
 *   2. Hugging Face RMBG-1.4  — HUGGINGFACE_TOKEN set  (free AI model)
 *   3. GD edge-seeded flood-fill — pure PHP, no keys required
 *
 * Input  payload keys: processedImage (current working image, may be deskewed)
 * Output payload keys: processedImage (path to bg-removed PNG)
 *                      backgroundRemoved (bool — always true after this agent)
 */
class BackgroundRemovalAgent
{
    /** Max dimension (px) for the flood-fill work image — higher = more detail at edges. */
    private const FLOOD_WORK_SIZE = 800;

    /**
     * Per-zone colour-distance tolerance (Euclidean RGB, 0–441 scale).
     * Each edge strip is compared against its own sampled colour, so this
     * can stay conservative without missing multi-coloured backgrounds.
     */
    private const FLOOD_TOLERANCE = 52.0;

    /** Fallback safe-zone margins when no faceBox is available. */
    private const SAFE_MARGIN_X = 0.15;
    private const SAFE_MARGIN_Y = 0.10;

    public static function handle(array $payload): array
    {
        // Use the current working image (may have been deskewed/rotated by a prior agent).
        // faceBox coordinates always reference processedImage, so we must use the same file.
        $sourcePath = $payload['processedImage'];
        $outputPath = storage_path('app/tmp/' . Str::uuid() . '.png');
        @mkdir(dirname($outputPath), 0755, true);

        $removeBgKey  = config('services.removebg.key', '');
        $hfToken      = config('services.huggingface.token', '');
        $pythonApiUrl = config('services.ai_pipeline.url', '');   // e.g. http://localhost:8000

        if (! empty($pythonApiUrl)) {
            // Tier 0: Local Python AI service (U²-Net via FastAPI) — best quality, free
            self::removeViaPythonService($sourcePath, $outputPath, $pythonApiUrl, $payload['faceBox'] ?? null);
        } elseif (! empty($removeBgKey)) {
            self::removeViaRemoveBg($sourcePath, $outputPath, $removeBgKey);
        } elseif (! empty($hfToken)) {
            self::removeViaHuggingFace($sourcePath, $outputPath, $hfToken, $payload['faceBox'] ?? null);
        } else {
            self::floodFillRemoveBackground($sourcePath, $outputPath, $payload['faceBox'] ?? null);
        }

        $payload['processedImage']    = $outputPath;
        $payload['backgroundRemoved'] = true;

        return $payload;
    }

    // ── Python AI pipeline (local FastAPI + U²-Net) ────────────────────────────

    /**
     * Calls the local Python FastAPI service (ai-system/api/main.py).
     * POST /remove-background — returns a white-background PNG directly.
     * Falls back to flood-fill if the service is unreachable.
     */
    private static function removeViaPythonService(
        string  $source,
        string  $dest,
        string  $baseUrl,
        ?array  $faceBox = null
    ): void {
        try {
            $client = new Client(['timeout' => 30]);
            $client->post(rtrim($baseUrl, '/') . '/remove-background', [
                'multipart' => [
                    ['name' => 'image',    'contents' => fopen($source, 'r')],
                    ['name' => 'white_bg', 'contents' => 'true'],
                ],
                'sink' => $dest,
            ]);
        } catch (\Throwable $e) {
            Log::warning('BackgroundRemovalAgent: Python service unavailable, using flood-fill fallback.', [
                'error' => $e->getMessage(),
            ]);
            self::floodFillRemoveBackground($source, $dest, $faceBox);
        }
    }

    // ── remove.bg API ──────────────────────────────────────────────────────────

    private static function removeViaRemoveBg(string $source, string $dest, string $apiKey): void
    {
        $client = new Client(['timeout' => 30]);

        $client->post('https://api.remove.bg/v1.0/removebg', [
            'headers'   => ['X-Api-Key' => $apiKey],
            'multipart' => [
                ['name' => 'image_file', 'contents' => fopen($source, 'r')],
                ['name' => 'size',       'contents' => 'auto'],
                ['name' => 'bg_color',   'contents' => 'ffffff'],
            ],
            'sink' => $dest,
        ]);
    }

    // ── Hugging Face RMBG-1.4 (free AI background removal) ────────────────────

    /**
     * Calls the BRIA RMBG-1.4 model on Hugging Face Inference API.
     * The model returns a PNG with a transparent background; we then
     * composite it onto a white canvas.
     *
     * Free tier: https://huggingface.co/briaai/RMBG-1.4
     * Token:     create a free account → Settings → Access Tokens
     */
    private static function removeViaHuggingFace(string $source, string $dest, string $token, ?array $faceBox = null): void
    {
        try {
            $client  = new Client(['timeout' => 60]);
            $tmpPath = storage_path('app/tmp/' . Str::uuid() . '_hf_mask.png');

            $client->post('https://api-inference.huggingface.co/models/briaai/RMBG-1.4', [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                    'Content-Type'  => 'application/octet-stream',
                ],
                'body' => fopen($source, 'r'),
                'sink' => $tmpPath,
            ]);

            // Composite transparent result onto a white canvas
            $manager = new ImageManager(new Driver());
            $fg      = $manager->read($tmpPath);
            $canvas  = $manager->create($fg->width(), $fg->height())->fill('ffffff');
            $canvas->place($fg, 'center');
            $canvas->toPng()->save($dest);

            @unlink($tmpPath);

        } catch (\Throwable $e) {
            Log::warning('BackgroundRemovalAgent: Hugging Face failed, using flood-fill fallback.', [
                'error' => $e->getMessage(),
            ]);
            self::floodFillRemoveBackground($source, $dest, $faceBox);
        }
    }

    // ── GD edge-seeded flood-fill (pure PHP, no external API required) ─────────

    /**
     * Removes the background without any external service.
     *
     * Algorithm:
     *  1. Downsample image to ≤800 px for fast BFS.
     *  2. Build a face-guided safe zone from faceBox so the BFS can NEVER
     *     touch the subject (scalp, ears, shoulders, glasses).
     *  3. Sample the background colour INDEPENDENTLY for each of the four
     *     edges (top/bottom/left/right strips), excluding pixels that fall
     *     within the safe-zone projection on that axis.  This gives 4 colour
     *     references so non-uniform backgrounds (bright window on one side,
     *     pink curtain on another) are each handled by their own reference
     *     instead of a blended average that matches nothing accurately.
     *     A plain-white reference is always appended so overexposed areas
     *     (blown-out windows / sky) are always erased.
     *  4. Single BFS from all edge pixels; each pixel is accepted as
     *     background if it matches ANY of the sampled colours within
     *     FLOOD_TOLERANCE.  Stops at safe-zone boundary.
     *  5. Apply mask at work resolution, then bilinear-resample back to
     *     original size for natural soft edges.
     */
    private static function floodFillRemoveBackground(string $source, string $dest, ?array $faceBox = null): void
    {
        $ext      = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        $original = in_array($ext, ['jpg', 'jpeg'])
            ? imagecreatefromjpeg($source)
            : imagecreatefrompng($source);

        if (! $original) {
            [$w, $h] = getimagesize($source);
            $blank   = imagecreatetruecolor($w ?: 600, $h ?: 600);
            imagefill($blank, 0, 0, imagecolorallocate($blank, 255, 255, 255));
            imagepng($blank, $dest);
            return;
        }

        $imgW = imagesx($original);
        $imgH = imagesy($original);

        // ── 1. Downsample ──────────────────────────────────────────────────
        $scale = min(1.0, self::FLOOD_WORK_SIZE / max($imgW, $imgH));
        $workW = max(1, (int) ($imgW * $scale));
        $workH = max(1, (int) ($imgH * $scale));
        $work  = imagecreatetruecolor($workW, $workH);
        imagecopyresampled($work, $original, 0, 0, 0, 0, $workW, $workH, $imgW, $imgH);

        // ── 2. Face-guided safe zone ───────────────────────────────────────
        // Padding ratios (relative to face box):
        //   side   +40 %  — ears, hair, glasses temples
        //   top    +65 %  — full scalp including bald / very short hair
        //   bottom +110 % — chin, neck, shirt collar, shoulders
        if ($faceBox && isset($faceBox['img_w']) && $faceBox['img_w'] > 0) {
            $fx = $faceBox['x']      * $scale;
            $fy = $faceBox['y']      * $scale;
            $fw = $faceBox['width']  * $scale;
            $fh = $faceBox['height'] * $scale;

            $safeLeft   = max(0.0,            $fx - $fw * 0.40);
            $safeRight  = min((float) $workW,  $fx + $fw * 1.40);
            $safeTop    = max(0.0,            $fy - $fh * 0.65);
            $safeBottom = min((float) $workH,  $fy + $fh * 2.10);
        } else {
            $safeLeft   = $workW * self::SAFE_MARGIN_X;
            $safeRight  = $workW * (1.0 - self::SAFE_MARGIN_X);
            $safeTop    = $workH * self::SAFE_MARGIN_Y;
            $safeBottom = $workH * (1.0 - self::SAFE_MARGIN_Y);
        }

        // ── 3. Multi-zone background sampling ─────────────────────────────
        $sz      = max(8, (int) (min($workW, $workH) * 0.08));
        $samples = self::sampleMultiZone($work, $workW, $workH, $sz,
                                         $safeLeft, $safeRight, $safeTop, $safeBottom);
        $tolSq   = self::FLOOD_TOLERANCE ** 2;

        // ── 4. BFS flood-fill ──────────────────────────────────────────────
        $mask    = [];
        $visited = [];
        $queue   = new \SplQueue();

        // Seed only from edge pixels that lie outside the safe zone.
        for ($x = 0; $x < $workW; $x++) {
            if (! self::inSafeZone($x, 0,           $safeLeft, $safeRight, $safeTop, $safeBottom)) {
                $queue->enqueue([$x, 0]);
            }
            if (! self::inSafeZone($x, $workH - 1, $safeLeft, $safeRight, $safeTop, $safeBottom)) {
                $queue->enqueue([$x, $workH - 1]);
            }
        }
        for ($y = 1; $y < $workH - 1; $y++) {
            if (! self::inSafeZone(0,          $y, $safeLeft, $safeRight, $safeTop, $safeBottom)) {
                $queue->enqueue([0, $y]);
            }
            if (! self::inSafeZone($workW - 1, $y, $safeLeft, $safeRight, $safeTop, $safeBottom)) {
                $queue->enqueue([$workW - 1, $y]);
            }
        }

        while (! $queue->isEmpty()) {
            [$x, $y] = $queue->dequeue();

            if ($x < 0 || $x >= $workW || $y < 0 || $y >= $workH) {
                continue;
            }

            $key = $y * $workW + $x;
            if (isset($visited[$key])) {
                continue;
            }
            $visited[$key] = true;

            if (self::inSafeZone($x, $y, $safeLeft, $safeRight, $safeTop, $safeBottom)) {
                continue;  // absolute barrier — never erase subject pixels
            }

            $c = imagecolorat($work, $x, $y);
            $r = ($c >> 16) & 0xFF;
            $g = ($c >>  8) & 0xFF;
            $b =  $c        & 0xFF;

            // Accept if within tolerance of ANY zone's sampled background colour.
            $isBackground = false;
            foreach ($samples as [$bgR, $bgG, $bgB]) {
                if (($r - $bgR) ** 2 + ($g - $bgG) ** 2 + ($b - $bgB) ** 2 <= $tolSq) {
                    $isBackground = true;
                    break;
                }
            }
            if (! $isBackground) {
                continue;
            }

            $mask[$key] = true;
            $queue->enqueue([$x + 1, $y]);
            $queue->enqueue([$x - 1, $y]);
            $queue->enqueue([$x,     $y + 1]);
            $queue->enqueue([$x,     $y - 1]);
        }

        // ── 5. Apply mask at work resolution ──────────────────────────────
        $wc = imagecolorallocate($work, 255, 255, 255);
        foreach ($mask as $key => $_) {
            imagesetpixel($work, $key % $workW, intdiv($key, $workW), $wc);
        }

        // ── 6. Bilinear resample back to original size ─────────────────────
        $output = imagecreatetruecolor($imgW, $imgH);
        imagefill($output, 0, 0, imagecolorallocate($output, 255, 255, 255));
        imagecopyresampled($output, $work, 0, 0, 0, 0, $imgW, $imgH, $workW, $workH);

        imagepng($output, $dest);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Sample 4 independent background colour references — one per image edge.
     *
     * Each strip is sampled only from pixels whose position on the perpendicular
     * axis falls OUTSIDE the safe zone, so face/skin pixels never contaminate
     * the background reference.  This is critical for selfies where the subject
     * occupies an edge of the frame.
     *
     * Returns up to 4 [R, G, B] references (fewer if a strip has no valid pixels).
     *
     * @return list<array{0:int,1:int,2:int}>
     */
    private static function sampleMultiZone(
        \GdImage $gd,
        int      $w,
        int      $h,
        int      $sz,
        float    $safeL,
        float    $safeR,
        float    $safeT,
        float    $safeB
    ): array {
        $zones = [
            // [axis, fixed-range, free-range, direction]
            // top strip: y in [0, $sz),  skip x inside safe zone
            'top'    => static function () use ($gd, $w, $h, $sz, $safeL, $safeR): ?array {
                $rs = $gs = $bs = 0; $n = 0;
                for ($y = 0; $y < min($sz, $h); $y++) {
                    for ($x = 0; $x < $w; $x++) {
                        if ((float) $x > $safeL && (float) $x < $safeR) { continue; }
                        $c = imagecolorat($gd, $x, $y);
                        $rs += ($c >> 16) & 0xFF; $gs += ($c >> 8) & 0xFF; $bs += $c & 0xFF; $n++;
                    }
                }
                return $n > 0 ? [(int) round($rs / $n), (int) round($gs / $n), (int) round($bs / $n)] : null;
            },
            // bottom strip
            'bottom' => static function () use ($gd, $w, $h, $sz, $safeL, $safeR): ?array {
                $rs = $gs = $bs = 0; $n = 0;
                for ($y = max(0, $h - $sz); $y < $h; $y++) {
                    for ($x = 0; $x < $w; $x++) {
                        if ((float) $x > $safeL && (float) $x < $safeR) { continue; }
                        $c = imagecolorat($gd, $x, $y);
                        $rs += ($c >> 16) & 0xFF; $gs += ($c >> 8) & 0xFF; $bs += $c & 0xFF; $n++;
                    }
                }
                return $n > 0 ? [(int) round($rs / $n), (int) round($gs / $n), (int) round($bs / $n)] : null;
            },
            // left strip
            'left'   => static function () use ($gd, $w, $h, $sz, $safeT, $safeB): ?array {
                $rs = $gs = $bs = 0; $n = 0;
                for ($x = 0; $x < min($sz, $w); $x++) {
                    for ($y = 0; $y < $h; $y++) {
                        if ((float) $y > $safeT && (float) $y < $safeB) { continue; }
                        $c = imagecolorat($gd, $x, $y);
                        $rs += ($c >> 16) & 0xFF; $gs += ($c >> 8) & 0xFF; $bs += $c & 0xFF; $n++;
                    }
                }
                return $n > 0 ? [(int) round($rs / $n), (int) round($gs / $n), (int) round($bs / $n)] : null;
            },
            // right strip
            'right'  => static function () use ($gd, $w, $h, $sz, $safeT, $safeB): ?array {
                $rs = $gs = $bs = 0; $n = 0;
                for ($x = max(0, $w - $sz); $x < $w; $x++) {
                    for ($y = 0; $y < $h; $y++) {
                        if ((float) $y > $safeT && (float) $y < $safeB) { continue; }
                        $c = imagecolorat($gd, $x, $y);
                        $rs += ($c >> 16) & 0xFF; $gs += ($c >> 8) & 0xFF; $bs += $c & 0xFF; $n++;
                    }
                }
                return $n > 0 ? [(int) round($rs / $n), (int) round($gs / $n), (int) round($bs / $n)] : null;
            },
        ];

        $samples = [];
        foreach ($zones as $fn) {
            $s = $fn();
            if ($s !== null) {
                $samples[] = $s;
            }
        }

        // Always include a plain-white reference so the BFS can erase
        // overexposed (blown-out) window / sky areas.
        $samples[] = [255, 255, 255];

        return $samples;
    }

    private static function inSafeZone(
        float $x,
        float $y,
        float $sl,
        float $sr,
        float $st,
        float $sb
    ): bool {
        return $x >= $sl && $x <= $sr && $y >= $st && $y <= $sb;
    }
}
