<?php

namespace Djinson\OpenAiMcp\app\AI;

use Djinson\OpenAiMcp\app\AI\Contracts\LlmClientInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Azure OpenAI implementation of LlmClientInterface.
 */
class AzureLlmClient implements LlmClientInterface
{
    public function call(array $messages, array $functions = [], string $functionCall = 'auto'): array
    {
        $cfg = config('openai-mcp.drivers.azure');
        $modelOptions = $cfg['model_options'];
        $retries = config('openai-mcp.retries.client', 3);
        $sleep = config('openai-mcp.retries.sleep_ms', 100);
        $timeout = config('openai-mcp.timeouts.request', 30);

        // Robust URL construction
        $endpoint = rtrim($cfg['endpoint'], '/');
        $deployment = $cfg['deployment'];
        $apiVersion = $cfg['api_version'];
        
        $url = sprintf(
            '%s/openai/deployments/%s/chat/completions?api-version=%s',
            $endpoint,
            $deployment,
            $apiVersion
        );

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
            'temperature' => $modelOptions['temperature'],
            'top_p'       => $modelOptions['top_p'],
            'max_tokens'  => $modelOptions['max_tokens'],
            'stream'      => false,
        ], $payload);

        Log::debug('LLM Request', ['url' => $url, 'body' => $body]);

        try {
            return retry($retries, function () use ($url, $cfg, $body, $timeout) {
                $response = Http::withHeaders([
                    'api-key'      => $cfg['key'],
                    'Content-Type' => 'application/json',
                ])
                ->timeout($timeout)
                ->post($url, $body)
                ->throw();

                $json = $response->json();

                // Validate response structure
                if (!isset($json['choices']) || !is_array($json['choices'])) {
                    throw new \RuntimeException('Invalid LLM response: missing choices key. Response: ' . json_encode($json));
                }

                return $json;
            }, $sleep);
        } catch (\Exception $e) {
            Log::error('LLM call failed', ['exception' => $e, 'body' => $body]);
            throw \Djinson\OpenAiMcp\app\AI\Exceptions\LlmException::fromException($e);
        }
    }

}
