<?php

namespace Djinson\OpenAiMcp\Tests\Unit;

use Djinson\OpenAiMcp\Tests\TestCase;
use Djinson\OpenAiMcp\app\AI\OpenAiLlmClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Djinson\OpenAiMcp\app\AI\Exceptions\LlmException;

class OpenAiLlmClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('openai-mcp.drivers.openai.key', 'test-key');
        Config::set('openai-mcp.drivers.openai.model', 'gpt-4');
        Config::set('openai-mcp.drivers.openai.model_options.temperature', 0.7);
        Config::set('openai-mcp.drivers.openai.model_options.top_p', 1.0);
        Config::set('openai-mcp.drivers.openai.model_options.max_tokens', 100);
        Config::set('openai-mcp.retries.client', 1);
        Config::set('openai-mcp.retries.sleep_ms', 1);
        Config::set('openai-mcp.timeouts.request', 5);
    }

    public function test_call_sends_correct_request()
    {
        Http::fake([
            'api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => 'Hello']]]], 200),
        ]);

        $client = new OpenAiLlmClient();
        $response = $client->call([['role' => 'user', 'content' => 'Hi']]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.openai.com/v1/chat/completions' &&
                   $request['model'] === 'gpt-4' &&
                   $request['messages'][0]['content'] === 'Hi' &&
                   $request->hasHeader('Authorization', 'Bearer test-key');
        });

        $this->assertEquals(['choices' => [['message' => ['content' => 'Hello']]]], $response);
    }
}
