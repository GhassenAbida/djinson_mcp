<?php

namespace Djinson\OpenAiMcp\Tests\Unit;

use Djinson\OpenAiMcp\Tests\TestCase;
use Djinson\OpenAiMcp\app\AI\LlmClientManager;
use Djinson\OpenAiMcp\app\AI\AzureLlmClient;
use Djinson\OpenAiMcp\app\AI\OpenAiLlmClient;
use Djinson\OpenAiMcp\app\AI\GeminiLlmClient;
use Illuminate\Support\Facades\Config;

class LlmClientManagerTest extends TestCase
{
    public function test_resolves_azure_driver()
    {
        Config::set('openai-mcp.default', 'azure');
        $manager = new LlmClientManager($this->app);
        $this->assertInstanceOf(AzureLlmClient::class, $manager->driver());
    }

    public function test_resolves_openai_driver()
    {
        Config::set('openai-mcp.default', 'openai');
        $manager = new LlmClientManager($this->app);
        $this->assertInstanceOf(OpenAiLlmClient::class, $manager->driver());
    }

    public function test_resolves_gemini_driver()
    {
        Config::set('openai-mcp.default', 'gemini');
        $manager = new LlmClientManager($this->app);
        $this->assertInstanceOf(GeminiLlmClient::class, $manager->driver());
    }
}
