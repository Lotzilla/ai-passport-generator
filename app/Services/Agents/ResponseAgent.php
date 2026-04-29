<?php

namespace App\Services\Agents;

use GuzzleHttp\Client;

/**
 * Agent 7 — Response Agent  (Chatbot)
 *
 * Responsibility: Convert pipeline results into user-friendly messages.
 *
 * Uses OpenAI when OPENAI_API_KEY is set.
 * Falls back to deterministic template matching — no AI required.
 *
 * Input  payload keys: status, errors
 * Output payload keys: message
 */
class ResponseAgent
{
    /**
     * Rule-based fallback messages keyed by partial error strings.
     */
    private static array $templates = [
        'Face too small'      => 'Your face appears too small. Move closer to the camera and make sure your face fills most of the frame.',
        'Face too large'      => 'Your face is too close to the camera. Step back slightly and retake the photo.',
        'Face not centered'   => 'Your face is not centred in the photo. Look straight at the camera and position yourself in the middle of the frame.',
        'No face'             => "We couldn't detect a face. Ensure your full face is visible, well-lit, and looking directly at the camera.",
    ];

    public static function handle(array $payload): array
    {
        if ($payload['status'] === 'success') {
            $payload['message'] = 'Your passport photo is ready! Click the download button below.';
            return $payload;
        }

        $apiKey = config('services.openai.key', '');

        $payload['message'] = ! empty($apiKey)
            ? self::generateWithOpenAI($payload['errors'], $apiKey)
            : self::buildRuleBasedMessage($payload['errors']);

        return $payload;
    }

    private static function generateWithOpenAI(array $errors, string $apiKey): string
    {
        try {
            $client    = new Client(['timeout' => 15]);
            $errorList = implode('; ', $errors);

            $response = $client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'    => 'gpt-4o-mini',
                    'messages' => [
                        [
                            'role'    => 'system',
                            'content' => 'You are a friendly assistant for a passport photo tool. Give brief, actionable advice (1-2 sentences) to help the user fix their photo.',
                        ],
                        [
                            'role'    => 'user',
                            'content' => "Passport photo validation failed with: {$errorList}. Tell the user how to fix it.",
                        ],
                    ],
                    'max_tokens' => 120,
                ],
            ]);

            $data = json_decode((string) $response->getBody(), true);
            return $data['choices'][0]['message']['content'] ?? self::buildRuleBasedMessage($errors);
        } catch (\Throwable) {
            return self::buildRuleBasedMessage($errors);
        }
    }

    private static function buildRuleBasedMessage(array $errors): string
    {
        if (empty($errors)) {
            return 'Processing failed. Please try again with a clearer, well-lit photo.';
        }

        $messages = [];
        foreach ($errors as $error) {
            $matched = false;
            foreach (self::$templates as $keyword => $msg) {
                if (stripos($error, $keyword) !== false) {
                    $messages[] = $msg;
                    $matched    = true;
                    break;
                }
            }
            if (! $matched) {
                $messages[] = $error;
            }
        }

        return implode(' ', array_unique($messages));
    }
}
