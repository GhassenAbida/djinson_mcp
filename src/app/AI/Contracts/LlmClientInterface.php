<?php

namespace Djinson\OpenAiMcp\app\AI\Contracts;

/**
 * Defines a generic LLM client interface.
 */
interface LlmClientInterface
{
    /**
     * Call the LLM with messages, optional function schemas, and function_call mode.
     *
     * @param array $messages
     * @param array $functions
     * @param string $functionCall
     * @return array
     */
    public function call(array $messages, array $functions = [], string $functionCall = 'auto'): array;
}
