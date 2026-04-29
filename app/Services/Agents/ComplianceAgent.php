<?php

namespace App\Services\Agents;

use App\Services\PassportRulesService;
use Illuminate\Support\Facades\Log;

/**
 * Agent 6 — Compliance Agent  (ADVISORY — never blocks generation)
 *
 * The pipeline auto-corrects all input conditions (distance, background,
 * lighting, tilt), so compliance is informational only.  The photo is
 * always saved and returned to the user.
 *
 * Notes logged (but never fail the pipeline):
 *   • Face off-centre after crop (indicates a face-detection accuracy issue)
 *
 * Face height percentage is NOT checked here — CropAgent mathematically
 * guarantees the face fills exactly the midpoint of the allowed range.
 */
class ComplianceAgent
{
    public static function handle(array $payload): array
    {
        $faceBox = $payload['faceBox'];

        // Advisory: horizontal centring check
        $faceCentreX = $faceBox['x'] + $faceBox['width'] / 2;
        $offsetPct   = abs($faceCentreX - ($faceBox['img_w'] / 2)) / $faceBox['img_w'];

        if ($offsetPct > 0.20) {
            Log::info('[Compliance] Face slightly off-centre', [
                'offset_pct' => round($offsetPct * 100, 1),
            ]);
        }

        // Always succeed — auto-processing handles all photo conditions
        $payload['status'] = 'success';

        return $payload;
    }
}
