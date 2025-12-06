<?php

namespace Djinson\OpenAiMcp\app\AI;

use Djinson\OpenAiMcp\app\AI\Contracts\LlmClientInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiLlmClient implements LlmClientInterface
{
    public function call(array $messages, array $functions = [], string $functionCall = 'auto'): array
    {
        $cfg = config('openai-mcp.drivers.gemini');
        $modelOptions = $cfg['model_options'];
        $retries = config('openai-mcp.retries.client', 3);
        $sleep = config('openai-mcp.retries.sleep_ms', 100);
        $timeout = config('openai-mcp.timeouts.request', 30);

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$cfg['model']}:generateContent?key={$cfg['key']}";

        // Convert OpenAI message format to Gemini format
        $contents = array_map(function ($msg) {
            return [
                'role' => $msg['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $msg['content']]]
            ];
        }, $messages);

        // Gemini tools format is different, simplified here for MVP
        // Note: Full tool support for Gemini requires specific schema mapping
        $tools = [];
        if (count($functions) > 0) {
             $tools = [['function_declarations' => $functions]];
        }

        $body = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $modelOptions['temperature'],
                'topP'        => $modelOptions['top_p'],
                'maxOutputTokens' => $modelOptions['max_tokens'],
            ]
        ];

        if (!empty($tools)) {
            $body['tools'] = $tools;
        }

        Log::debug('Gemini Request', ['url' => $url, 'body' => $body]);

        try {
            return retry($retries, function () use ($url, $body, $timeout) {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->timeout($timeout)
                ->post($url, $body)
                ->throw();

                // Transform Gemini response to OpenAI format for compatibility
                $json = $response->json();
                $content = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
                
                return [
                    'choices' => [
                        [
                            'message' => [
                                'role' => 'assistant',
                                'content' => $content
                            ]
                        ]
                    ]
                ];
            }, $sleep);
        } catch (\Exception $e) {
            Log::error('Gemini call failed', ['exception' => $e, 'body' => $body]);
            throw \Djinson\OpenAiMcp\app\AI\Exceptions\LlmException::fromException($e);
        }
    }
}
