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
        $maxSteps = config('openai-mcp.retries.max_steps', 10);

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
        $steps = 0;

        while (isset($choice['function_call'])) {
            if ($steps >= $maxSteps) {
                Log::warning('Max conversation steps reached', ['steps' => $steps]);
                // Break the loop and return what we have or a specific message
                // For now, we'll try to get a final response from the LLM or just return the last content if any.
                // But usually if it's function calling, content is null.
                // Let's force a final call without functions to get a summary/apology.
                $messages[] = [
                    'role' => 'system',
                    'content' => 'System: Maximum conversation steps reached. Please summarize the progress or explain why the task could not be completed.'
                ];
                $finalResp = $this->client->call($messages, [], 'none');
                return $finalResp['choices'][0]['message']['content'] ?? 'Max steps reached.';
            }

            $steps++;
            $fc = $choice['function_call'];

            // Build a unique key for this call
            $currentKey = $fc['name'] . '|' . ($fc['arguments'] ?? '');

            // If we’ve ever run this exact call before, we have a cycle
            if (in_array($currentKey, $seenCalls, true)) {
                Log::warning('Function call cycle detected', ['function' => $fc['name']]);
                // Feed back error to LLM
                $messages[] = $choice;
                $messages[] = [
                    'role'    => 'function',
                    'name'    => $fc['name'],
                    'content' => json_encode(['error' => "Cycle detected: You have already called this tool with these exact arguments. Please try a different approach."]),
                ];
            } else {
                // Record this call
                $seenCalls[] = $currentKey;

                // Decode arguments
                $args = json_decode($fc['arguments'] ?? '{}', true);
                if (!is_array($args)) {
                    // Feed back error
                    $messages[] = $choice;
                    $messages[] = [
                        'role'    => 'function',
                        'name'    => $fc['name'],
                        'content' => json_encode(['error' => "Invalid JSON arguments provided."]),
                    ];
                } else {
                    Log::info('Executing Tool', ['tool' => $fc['name'], 'args' => $args]);

                    // Execute the tool
                    try {
                        $result = $this->tools
                            ->get($fc['name'])
                            ->execute($args);
                        
                        $messages[] = $choice;
                        $messages[] = [
                            'role'    => 'function',
                            'name'    => $fc['name'],
                            'content' => json_encode($result, JSON_UNESCAPED_UNICODE),
                        ];
                    } catch (\Exception $e) {
                        Log::error('Tool execution failed', ['tool' => $fc['name'], 'exception' => $e]);
                        // Feed error back to LLM
                        $messages[] = $choice;
                        $messages[] = [
                            'role'    => 'function',
                            'name'    => $fc['name'],
                            'content' => json_encode(['error' => "Tool execution failed: " . $e->getMessage()]),
                        ];
                    }
                }
            }

            // Ask the model again
            try {
                $next = $this->client->call($messages, $functions);
                $choice = $next['choices'][0]['message'] ?? null;
            } catch (\Exception $e) {
                 Log::error('LLM call failed during loop', ['exception' => $e]);
                 throw $e;
            }

            if (! $choice) {
                Log::error('LLM response missing during orchestration', ['response' => $next ?? null]);
                throw new \RuntimeException('LLM response missing in loop');
            }
        }

        return $choice['content'];
    }
}
