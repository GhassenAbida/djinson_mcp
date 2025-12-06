<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default LLM Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default LLM driver that will be used to make
    | requests. You may set this to any of the connections defined in the
    | "drivers" array below.
    |
    | Supported: "azure", "openai", "gemini"
    |
    */

    'default' => env('LLM_DRIVER', 'azure'),

    /*
    |--------------------------------------------------------------------------
    | LLM Drivers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the settings for each driver.
    |
    */

    'drivers' => [
        'azure' => [
            'key'           => env('AZURE_OPENAI_KEY', ''),
            'endpoint'      => env('AZURE_OPENAI_ENDPOINT', ''),
            'api_version'   => env('AZURE_OPENAI_API_VERSION', '2024-02-15-preview'),
            'deployment'    => env('AZURE_OPENAI_DEPLOYMENT', 'gpt-4'),
            'model_options' => [
                'temperature' => (float) env('AZURE_OPENAI_TEMPERATURE', 0.4),
                'top_p'       => (float) env('AZURE_OPENAI_TOP_P', 0.95),
                'max_tokens'  => (int) env('AZURE_OPENAI_MAX_TOKENS', 800),
            ],
        ],

        'openai' => [
            'key'           => env('OPENAI_API_KEY', ''),
            'model'         => env('OPENAI_MODEL', 'gpt-4'),
            'organization'  => env('OPENAI_ORGANIZATION', null),
            'model_options' => [
                'temperature' => (float) env('OPENAI_TEMPERATURE', 0.7),
                'top_p'       => (float) env('OPENAI_TOP_P', 1.0),
                'max_tokens'  => (int) env('OPENAI_MAX_TOKENS', 800),
            ],
        ],

        'gemini' => [
            'key'           => env('GEMINI_API_KEY', ''),
            'model'         => env('GEMINI_MODEL', 'gemini-pro'),
            'model_options' => [
                'temperature' => (float) env('GEMINI_TEMPERATURE', 0.7),
                'top_p'       => (float) env('GEMINI_TOP_P', 0.95),
                'max_tokens'  => (int) env('GEMINI_MAX_TOKENS', 800),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resilience & Timeouts
    |--------------------------------------------------------------------------
    */
    'retries' => [
        'client'       => (int) env('OPENAI_MCP_CLIENT_RETRIES', 3),
        'orchestrator' => (int) env('OPENAI_MCP_ORCHESTRATOR_RETRIES', 2),
        'sleep_ms'     => (int) env('OPENAI_MCP_RETRY_SLEEP_MS', 100),
    ],

    'timeouts' => [
        'request' => (int) env('OPENAI_MCP_REQUEST_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource Paths
    |--------------------------------------------------------------------------
    */
    'paths' => [
        'prompts' => resource_path('ai-prompts'),
    ],

    /*
    |--------------------------------------------------------------------------
    | MCP Server-Sent-Events Endpoint
    |--------------------------------------------------------------------------
    */

    'mcp_sse_url'    => env('MCP_SSE_URL', ''),
];
