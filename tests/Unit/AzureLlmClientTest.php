<?php

namespace Djinson\OpenAiMcp\Tests\Unit;

use Djinson\OpenAiMcp\Tests\TestCase;
use Djinson\OpenAiMcp\app\AI\AzureLlmClient;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class AzureLlmClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('openai-mcp.drivers.azure', [
            'key' => 'test-key',
            'endpoint' => 'https://test-endpoint.com/', // Trailing slash to test rtrim
            'deployment' => 'test-deployment',
            'api_version' => '2024-02-15',
            'model_options' => [
                'temperature' => 0.5,
                'top_p' => 1.0,
                'max_tokens' => 100,
            ]
        ]);
        Config::set('openai-mcp.retries.client', 1);
        Config::set('openai-mcp.retries.sleep_ms', 0);
        Config::set('openai-mcp.timeouts.request', 10);
    }

    public function test_it_constructs_correct_url_and_validates_response()
    {
        Http::fake([
            'https://test-endpoint.com/openai/deployments/test-deployment/chat/completions?api-version=2024-02-15' => Http::response([
                'choices' => [['message' => ['content' => 'hello']]]
            ], 200)
        ]);

        $client = new AzureLlmClient();
        $response = $client->call([['role' => 'user', 'content' => 'hi']]);

        $this->assertEquals('hello', $response['choices'][0]['message']['content']);
    }

    public function test_it_throws_exception_on_invalid_response_structure()
    {
        Http::fake([
            '*' => Http::response(['error' => 'Something went wrong'], 200)
        ]);

        $this->expectException(\Djinson\OpenAiMcp\app\AI\Exceptions\LlmException::class);
        
        $client = new AzureLlmClient();
        $client->call([['role' => 'user', 'content' => 'hi']]);
    }
}
