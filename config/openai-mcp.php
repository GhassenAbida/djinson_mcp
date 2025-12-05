<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Azure OpenAI 4.1 Credentials
    |--------------------------------------------------------------------------
    */

    'azure_key'      => env('AZURE_OPENAI_KEY', ''),
    'azure_endpoint' => env('AZURE_OPENAI_ENDPOINT', ''),
    'api_version'    => env('AZURE_OPENAI_API_VERSION', '2024-02-15-preview'),

    /*
    |--------------------------------------------------------------------------
    | Model Options
    |--------------------------------------------------------------------------
    */
    'model_options' => [
        'deployment'  => env('AZURE_OPENAI_DEPLOYMENT', 'gpt-4'),
        'temperature' => (float) env('AZURE_OPENAI_TEMPERATURE', 0.4),
        'top_p'       => (float) env('AZURE_OPENAI_TOP_P', 0.95),
        'max_tokens'  => (int) env('AZURE_OPENAI_MAX_TOKENS', 800),
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
