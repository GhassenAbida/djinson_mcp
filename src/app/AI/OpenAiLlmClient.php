<?php

namespace Djinson\OpenAiMcp\app\AI;

use Djinson\OpenAiMcp\app\AI\Contracts\LlmClientInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiLlmClient implements LlmClientInterface
{
    public function call(array $messages, array $functions = [], string $functionCall = 'auto'): array
    {
        $cfg = config('openai-mcp.drivers.openai');
        $modelOptions = $cfg['model_options'];
        $retries = config('openai-mcp.retries.client', 3);
        $sleep = config('openai-mcp.retries.sleep_ms', 100);
        $timeout = config('openai-mcp.timeouts.request', 30);

        $url = 'https://api.openai.com/v1/chat/completions';

        $payload = [
            'model'    => $cfg['model'],
            'messages' => $messages,
        ];

        if (count($functions) > 0) {
            $payload['functions']     = array_values($functions);
            $payload['function_call'] = $functionCall;
        }

        $body = array_merge([
            'temperature' => $modelOptions['temperature'],
            'top_p'       => $modelOptions['top_p'],
            'max_tokens'  => $modelOptions['max_tokens'],
            'stream'      => false,
        ], $payload);

        Log::debug('OpenAI Request', ['url' => $url, 'body' => $body]);

        try {
            return retry($retries, function () use ($url, $cfg, $body, $timeout) {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $cfg['key'],
                    'Content-Type'  => 'application/json',
                ])
                ->timeout($timeout)
                ->post($url, $body)
                ->throw();

                return $response->json();
            }, $sleep);
        } catch (\Exception $e) {
            Log::error('OpenAI call failed', ['exception' => $e, 'body' => $body]);
            throw \Djinson\OpenAiMcp\app\AI\Exceptions\LlmException::fromException($e);
        }
    }
}
