<?php

namespace App\AI\Contracts;

/**
 * Manages a multi-turn conversation with function invocation.
 */
interface ConversationOrchestratorInterface
{
    /**
     * Handle a user prompt through the LLM and return final assistant content.
     *
     * @param string $userPrompt
     * @param array $functions
     * @return string
     */
    public function orchestrateConversation(string $userPrompt, array $functions = []): string;
}
