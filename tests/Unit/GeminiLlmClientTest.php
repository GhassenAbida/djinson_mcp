<?php

namespace Djinson\OpenAiMcp\Tests\Unit;

use Djinson\OpenAiMcp\Tests\TestCase;
use Djinson\OpenAiMcp\app\AI\GeminiLlmClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Djinson\OpenAiMcp\app\AI\Exceptions\LlmException;

class GeminiLlmClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('openai-mcp.drivers.gemini.key', 'test-key');
        Config::set('openai-mcp.drivers.gemini.model', 'gemini-pro');
        Config::set('openai-mcp.drivers.gemini.model_options.temperature', 0.7);
        Config::set('openai-mcp.drivers.gemini.model_options.top_p', 0.95);
        Config::set('openai-mcp.drivers.gemini.model_options.max_tokens', 100);
        Config::set('openai-mcp.retries.client', 1);
        Config::set('openai-mcp.retries.sleep_ms', 1);
        Config::set('openai-mcp.timeouts.request', 5);
    }

    public function test_call_sends_correct_request()
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'Hello']]]]
                ]
            ], 200),
        ]);

        $client = new GeminiLlmClient();
        $response = $client->call([['role' => 'user', 'content' => 'Hi']]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'generativelanguage.googleapis.com') &&
                   str_contains($request->url(), 'key=test-key') &&
                   $request['contents'][0]['parts'][0]['text'] === 'Hi';
        });

        $this->assertEquals('Hello', $response['choices'][0]['message']['content']);
    }
}
