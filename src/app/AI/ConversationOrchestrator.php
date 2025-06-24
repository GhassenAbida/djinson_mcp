<?php

namespace App\AI\Services;

use App\AI\Contracts\LlmClientInterface;
use App\AI\Contracts\ConversationOrchestratorInterface;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates message flow, function calls, and tool invocation.
 */
class ConversationOrchestrator implements ConversationOrchestratorInterface
{
    protected LlmClientInterface $client;
    protected \App\MCP\Contracts\ToolManagerInterface $tools;

    public function __construct(LlmClientInterface $client, \App\MCP\Contracts\ToolManagerInterface $tools)
    {
        $this->client = $client;
        $this->tools  = $tools;
    }

    public function orchestrateConversation(string $userPrompt, array $functions = []): string
    {
        // Load highest-level prompts
        $messages = [
            ['role' => 'system',    'content' => File::get(resource_path('ai-prompts/system.txt'))],
            ['role' => 'user',      'content' => File::get(resource_path('ai-prompts/user.txt'))],
            ['role' => 'assistant', 'content' => File::get(resource_path('ai-prompts/assistant.txt'))],
            ['role' => 'user',      'content' => $userPrompt],
        ];
        // Initial call with retry on missing choice
        try {
            $response = retry(2, function () use ($messages, $functions) {
                $resp = $this->client->call($messages, $functions, 'auto');

                // If the model returns no message, trigger a retry
                if (empty($resp['choices'][0]['message'])) {
                    throw new \RuntimeException('Empty LLM choice on initial call');
                }

                return $resp;
            }, 100);
        } catch (\Exception $e) {
            Log::error('Initial LLM call failed after retries', ['exception' => $e]);
            throw $e;
        }

        $choice = $response['choices'][0]['message'];

        $seenCalls = [];

        while (isset($choice['function_call'])) {
            $fc = $choice['function_call'];

            // Build a unique key for this call
            $currentKey = $fc['name'] . '|' . ($fc['arguments'] ?? '');

            // If we’ve ever run this exact call before, we have a cycle
            if (in_array($currentKey, $seenCalls, true)) {
                throw new \RuntimeException("Function call cycle detected: {$fc['name']}");
            }

            // Record this call
            $seenCalls[] = $currentKey;

            // Execute the tool
            $result = $this->tools
                ->get($fc['name'])
                ->execute(json_decode($fc['arguments'] ?? '{}', true));

            // Append function call + result
            $messages[] = $choice;
            $messages[] = [
                'role'    => 'function',
                'name'    => $fc['name'],
                'content' => json_encode($result, JSON_UNESCAPED_UNICODE),
            ];

            // Ask the model again
            $next   = $this->client->call($messages);
            $choice = $next['choices'][0]['message'] ?? null;

            if (! $choice) {
                Log::error('LLM response missing during orchestration', ['response' => $next]);
                throw new \RuntimeException('LLM response missing in loop');
            }
        }

        return $choice['content'];
            }
}
