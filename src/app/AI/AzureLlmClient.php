<?php

namespace App\AI\Services;

use App\AI\Contracts\LlmClientInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Azure OpenAI implementation of LlmClientInterface.
 */
class AzureLlmClient implements LlmClientInterface
{
   public function call(array $messages, array $functions = [], string $functionCall = 'auto'): array
{
    $cfg = config('services.azure_openai');
    $url = config('services.azure_openai.deployment_url');

    // Always include the core messages
    $payload = [
        'messages' => $messages,
    ];

    // Only attach function schema if we actually have at least one
    if (count($functions) > 0) {
        // Ensure $functions is a zero-based, numerically indexed array
        $payload['functions']     = array_values($functions);
        $payload['function_call'] = $functionCall;
    }

    // Merge in model parameters
    $body = array_merge([
        'temperature' => 0.4,
        'model'       => $cfg['deployment'],
        'stream'      => false,
    ], $payload);

    try {
        return retry(3, fn() => Http::withHeaders([
            'api-key'      => $cfg['key'],
            'Content-Type' => 'application/json',
        ])
        ->post($url, $body)
        ->throw()
        ->json(), 100);
    } catch (\Exception $e) {
        Log::error('LLM call failed', ['exception' => $e]);
        throw $e;
    }
}

}
