<?php

namespace Djinson\OpenAiMcp\Tests\Unit;

use Djinson\OpenAiMcp\Tests\TestCase;
use App\AI\Services\AzureLlmClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use App\AI\Exceptions\LlmException;

class AzureLlmClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock configuration
        Config::set('openai-mcp.azure_endpoint', 'https://example.com');
        Config::set('openai-mcp.azure_key', 'test-key');
        Config::set('openai-mcp.api_version', '2024-02-15-preview');
        Config::set('openai-mcp.model_options', [
            'deployment'  => 'gpt-4',
            'temperature' => 0.4,
            'top_p'       => 0.95,
            'max_tokens'  => 800,
        ]);
        Config::set('openai-mcp.retries.client', 1);
        Config::set('openai-mcp.retries.sleep_ms', 1);
        Config::set('openai-mcp.timeouts.request', 5);
    }

    public function test_call_sends_correct_request()
    {
        Http::fake([
            'example.com/*' => Http::response(['choices' => [['message' => ['content' => 'Hello']]]], 200),
        ]);

        $client = new AzureLlmClient();
        $response = $client->call([['role' => 'user', 'content' => 'Hi']]);

        $this->assertEquals('Hello', $response['choices'][0]['message']['content']);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://example.com/openai/deployments/gpt-4/chat/completions?api-version=2024-02-15-preview' &&
                   $request->hasHeader('api-key', 'test-key') &&
                   $request['messages'][0]['content'] == 'Hi';
        });
    }

    public function test_call_throws_llm_exception_on_failure()
    {
        Http::fake([
            'example.com/*' => Http::response('Error', 500),
        ]);

        $this->expectException(LlmException::class);

        $client = new AzureLlmClient();
        $client->call([['role' => 'user', 'content' => 'Hi']]);
    }
}
