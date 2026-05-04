<?php

namespace App\Services;

/**
 * Passport Rules Registry
 *
 * Configuration-based rule system for multi-country support.
 * Add new countries by extending the $rules array.
 *
 * dimensions_px: [width, height] at 300 dpi
 * face_height_percent: [min, max] fraction of photo height occupied by face
 */
class PassportRulesService
{
    private array $rules = [
        'US' => [
            'label'                => 'United States',
            'ratio'                => '2x2',
            'dimensions_px'        => [600, 600],      // 2"×2" at 300dpi
            'face_height_percent'  => [0.55, 0.65],    // US: face 50–69% of photo height
            'face_top_percent'     => 0.08,             // kept for reference
            'eye_position_percent' => 0.40,             // eyes at 40% from top
        ],
        'GB' => [
            'label'                => 'United Kingdom',
            'ratio'                => '35x45',
            'dimensions_px'        => [413, 531],      // 35×45mm at 300dpi
            'face_height_percent'  => [0.55, 0.65],    // UK: face 29–34mm in 45mm photo
            'face_top_percent'     => 0.09,
            'eye_position_percent' => 0.42,            // eyes ~42% from top — face centred in upper 80%
        ],
        'EU' => [
            'label'                => 'EU (Schengen)',
            'ratio'                => '35x45',
            'dimensions_px'        => [413, 531],
            'face_height_percent'  => [0.55, 0.65],
            'face_top_percent'     => 0.08,
            'eye_position_percent' => 0.38,
        ],
        'CA' => [
            'label'                => 'Canada',
            'ratio'                => '50x70',
            'dimensions_px'        => [591, 827],      // 50×70mm at 300dpi
            'face_height_percent'  => [0.50, 0.62],
            'face_top_percent'     => 0.10,
            'eye_position_percent' => 0.38,
        ],
        'AU' => [
            'label'                => 'Australia',
            'ratio'                => '35x45',
            'dimensions_px'        => [413, 531],
            'face_height_percent'  => [0.55, 0.65],
            'face_top_percent'     => 0.08,
            'eye_position_percent' => 0.38,
        ],
    ];

    public function get(string $country): array
    {
        return $this->rules[strtoupper($country)] ?? $this->rules['US'];
    }

    public function all(): array
    {
        return array_map(
            fn ($code, $rule) => ['code' => $code, 'label' => $rule['label']],
            array_keys($this->rules),
            $this->rules
        );
    }
}
