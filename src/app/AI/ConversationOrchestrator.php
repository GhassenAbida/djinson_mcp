<?php

namespace Djinson\OpenAiMcp\app\AI;

use Djinson\OpenAiMcp\app\AI\Contracts\LlmClientInterface;
use Djinson\OpenAiMcp\app\AI\Contracts\ConversationOrchestratorInterface;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates message flow, function calls, and tool invocation.
 */
class ConversationOrchestrator implements ConversationOrchestratorInterface
{
    protected LlmClientInterface $client;
    protected \Djinson\OpenAiMcp\app\MCP\Contracts\ToolManagerInterface $tools;

    public function __construct(LlmClientInterface $client, \Djinson\OpenAiMcp\app\MCP\Contracts\ToolManagerInterface $tools)
    {
        $this->client = $client;
        $this->tools  = $tools;
    }

    public function orchestrateConversation(string $userPrompt, array $functions = []): string
    {
        $promptPath = config('openai-mcp.paths.prompts', resource_path('ai-prompts'));
        $retries = config('openai-mcp.retries.orchestrator', 2);
        $sleep = config('openai-mcp.retries.sleep_ms', 100);

        // Load highest-level prompts
        $messages = [
            ['role' => 'system',    'content' => File::get($promptPath . '/system.txt')],
            ['role' => 'user',      'content' => File::get($promptPath . '/user.txt')],
            ['role' => 'assistant', 'content' => File::get($promptPath . '/assistant.txt')],
            ['role' => 'user',      'content' => $userPrompt],
        ];

        // Initial call with retry on missing choice
        try {
            $response = retry($retries, function () use ($messages, $functions) {
                $resp = $this->client->call($messages, $functions, 'auto');

                // If the model returns no message, trigger a retry
                if (empty($resp['choices'][0]['message'])) {
                    throw new \RuntimeException('Empty LLM choice on initial call');
                }

                return $resp;
            }, $sleep);
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
                Log::warning('Function call cycle detected', ['function' => $fc['name']]);
                throw new \RuntimeException("Function call cycle detected: {$fc['name']}");
            }

            // Record this call
            $seenCalls[] = $currentKey;

            // Decode arguments
            $args = json_decode($fc['arguments'] ?? '{}', true);
            if (!is_array($args)) {
                Log::error('Invalid tool arguments', ['function' => $fc['name'], 'arguments' => $fc['arguments']]);
                throw new \RuntimeException("Invalid arguments for tool: {$fc['name']}");
            }

            Log::info('Executing Tool', ['tool' => $fc['name'], 'args' => $args]);

            // Execute the tool
            try {
                $result = $this->tools
                    ->get($fc['name'])
                    ->execute($args);
            } catch (\Exception $e) {
                Log::error('Tool execution failed', ['tool' => $fc['name'], 'exception' => $e]);
                throw $e;
            }

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
