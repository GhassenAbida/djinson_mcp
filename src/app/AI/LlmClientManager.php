<?php

namespace Djinson\OpenAiMcp\app\AI;

use Illuminate\Support\Manager;
use Djinson\OpenAiMcp\app\AI\Contracts\LlmClientInterface;

class LlmClientManager extends Manager
{
    public function getDefaultDriver()
    {
        return $this->config->get('openai-mcp.default', 'azure');
    }

    public function createAzureDriver(): LlmClientInterface
    {
        return new AzureLlmClient();
    }

    public function createOpenaiDriver(): LlmClientInterface
    {
        return new OpenAiLlmClient();
    }

    public function createGeminiDriver(): LlmClientInterface
    {
        return new GeminiLlmClient();
    }
}
