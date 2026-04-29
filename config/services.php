<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'removebg' => [
        'key' => env('REMOVE_BG_API_KEY', ''),
    ],

    'huggingface' => [
        // Free AI background removal — BRIA RMBG-1.4 model.
        // Get a free token at https://huggingface.co/settings/tokens
        'token' => env('HUGGINGFACE_TOKEN', ''),
    ],

    // Local Python AI pipeline (ai-system/api/main.py running via uvicorn).
    // Set AI_PIPELINE_URL=http://localhost:8000 in .env to enable.
    // When set this takes priority over remove.bg / Hugging Face / flood-fill.
    'ai_pipeline' => [
        'url' => env('AI_PIPELINE_URL', ''),
    ],

    'incode' => [
        'enabled' => env('INCODE_ENABLED', true),
        'api_key' => env('INCODE_API_KEY', ''),
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY', ''),
    ],

];
