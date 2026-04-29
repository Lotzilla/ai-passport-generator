<?php

namespace App\Services\Pipeline;

use App\Services\Agents\AccessoryCheckAgent;
use App\Services\Agents\BackgroundRemovalAgent;
use App\Services\Agents\ComplianceAgent;
use App\Services\Agents\CropAgent;
use App\Services\Agents\DeskewAgent;
use App\Services\Agents\FaceDetectionAgent;
use App\Services\Agents\LightingAgent;
use App\Services\Agents\ResponseAgent;
use App\Services\Agents\UploadAgent;
use App\Services\PassportRulesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * PassportPipeline — Orchestrator
 *
 * Controls the full agent pipeline. Each agent:
 *   - Receives the shared payload
 *   - Modifies only its own fields
 *   - Returns the updated payload
 *
 * The orchestrator decides whether to continue or abort after each step.
 */
class PassportPipeline
{
    public function run(Request $request): array
    {
        Log::info('[Pipeline] Starting passport photo pipeline');

        // ── Step 1: Upload ────────────────────────────────────────────────
        $payload = UploadAgent::handle($request);
        Log::info('[Pipeline] Step 1 Upload complete', ['file' => basename($payload['originalImage'])]);

        // ── Step 2: Face Detection (on original, before background removal) ─
        // Running face detection first gives us the exact face bounding box,
        // which BackgroundRemovalAgent uses to protect the subject during fill.
        $payload = FaceDetectionAgent::handle($payload);
        Log::info('[Pipeline] Step 2 Face detection', ['detected' => $payload['faceDetected']]);

        // Critical gate: no face → abort pipeline
        if (! $payload['faceDetected']) {
            $payload['status']   = 'failed';
            $payload['errors'][] = 'No face detected in the photo.';
            Log::warning('[Pipeline] Aborted — no face detected');
            return ResponseAgent::handle($payload);
        }

        // ── Step 2.5: Deskew — auto-straighten head tilt ────────────────────
        // Uses MediaPipe eye landmarks to rotate the image so the eyes are
        // perfectly level before background removal and crop.
        $payload = DeskewAgent::handle($payload);
        Log::info('[Pipeline] Step 2.5 Deskew complete');

        // ── Step 3: Background Removal (guided by faceBox) ────────────────
        $payload = BackgroundRemovalAgent::handle($payload);
        Log::info('[Pipeline] Step 3 Background removal complete');

        // ── Step 3.5: Accessory Check (glasses & hats) ────────────────────
        // Runs after background removal — the white background makes the
        // pixel heuristics far more accurate (non-white = subject or accessory).
        $payload = AccessoryCheckAgent::handle($payload);
        Log::info('[Pipeline] Step 3.5 Accessory check complete', [
            'accessoryDetected' => $payload['accessoryDetected'] ?? false,
        ]);

        // Gate: forbidden accessory → abort with error
        if (! empty($payload['accessoryDetected'])) {
            Log::warning('[Pipeline] Aborted — forbidden accessory detected', [
                'types' => $payload['accessoryTypes'] ?? [],
            ]);
            return ResponseAgent::handle($payload);
        }

        // ── Step 4: Crop ──────────────────────────────────────────────────
        $payload = CropAgent::handle($payload);
        Log::info('[Pipeline] Step 4 Crop complete');

        // ── Step 5: Lighting Adjustment ───────────────────────────────────
        $payload = LightingAgent::handle($payload);
        Log::info('[Pipeline] Step 5 Lighting adjustment complete');

        // ── Step 6: Compliance Validation ─────────────────────────────────
        $payload = ComplianceAgent::handle($payload);
        Log::info('[Pipeline] Step 6 Compliance', ['status' => $payload['status']]);

        // Always finalise and save — the pipeline auto-corrects all conditions.
        $payload = $this->finalizeOutput($payload);

        // ── Step 7: Response Agent ────────────────────────────────────────
        $payload = ResponseAgent::handle($payload);
        Log::info('[Pipeline] Step 7 Response generated', ['message' => $payload['message']]);

        return $payload;
    }

    /**
     * Move the temp processed image into the permanent output directory.
     * Filename encodes country + pixel dimensions so the download is self-describing.
     */
    private function finalizeOutput(array $payload): array
    {
        $outputDir = storage_path('app/processed');
        @mkdir($outputDir, 0755, true);

        $country          = strtoupper($payload['country'] ?? 'US');
        $rules            = (new PassportRulesService())->get($country);
        [$width, $height] = $rules['dimensions_px'];

        $filename   = "passport-{$country}-{$width}x{$height}-" . Str::uuid() . '.png';
        $outputPath = $outputDir . DIRECTORY_SEPARATOR . $filename;

        rename($payload['processedImage'], $outputPath);

        $payload['processedImage'] = $outputPath;
        $payload['outputFilename'] = $filename;
        $payload['downloadUrl']    = route('photo.download', ['filename' => $filename]);

        return $payload;
    }
}
