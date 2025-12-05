<?php

namespace Djinson\OpenAiMcp\Tests\Unit;

use Djinson\OpenAiMcp\Tests\TestCase;
use Djinson\OpenAiMcp\app\AI\ConversationOrchestrator;
use Djinson\OpenAiMcp\app\AI\Contracts\LlmClientInterface;
use Djinson\OpenAiMcp\app\MCP\Contracts\ToolManagerInterface;
use Djinson\OpenAiMcp\app\MCP\Contracts\ToolInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Mockery;

class ConversationOrchestratorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('openai-mcp.retries.orchestrator', 1);
        Config::set('openai-mcp.retries.sleep_ms', 1);
        Config::set('openai-mcp.paths.prompts', __DIR__ . '/../../src/resources/ai-prompts');
        
        // Mock prompt files if they don't exist in test env, or rely on package files
        // For simplicity, we assume the path points to the real files or we mock File facade
        File::shouldReceive('get')->andReturn('prompt content');
    }

    public function test_orchestrate_conversation_returns_content()
    {
        $mockClient = Mockery::mock(LlmClientInterface::class);
        $mockTools = Mockery::mock(ToolManagerInterface::class);

        $mockClient->shouldReceive('call')
            ->once()
            ->andReturn(['choices' => [['message' => ['content' => 'Final Answer']]]]);

        $orchestrator = new ConversationOrchestrator($mockClient, $mockTools);
        $result = $orchestrator->orchestrateConversation('Hello');

        $this->assertEquals('Final Answer', $result);
    }

    public function test_orchestrate_conversation_handles_tool_calls()
    {
        $mockClient = Mockery::mock(LlmClientInterface::class);
        $mockTools = Mockery::mock(ToolManagerInterface::class);
        $mockTool = Mockery::mock(ToolInterface::class);

        // First call returns a function call
        $mockClient->shouldReceive('call')
            ->times(2)
            ->andReturn(
                // First response: Call tool
                ['choices' => [['message' => [
                    'content' => null,
                    'function_call' => [
                        'name' => 'test_tool',
                        'arguments' => '{"arg":"val"}'
                    ]
                ]]]],
                // Second response: Final answer
                ['choices' => [['message' => ['content' => 'Tool Result Processed']]]]
            );

        $mockTools->shouldReceive('get')->with('test_tool')->andReturn($mockTool);
        $mockTool->shouldReceive('execute')->with(['arg' => 'val'])->andReturn(['result' => 'success']);

        $orchestrator = new ConversationOrchestrator($mockClient, $mockTools);
        $result = $orchestrator->orchestrateConversation('Use tool');

        $this->assertEquals('Tool Result Processed', $result);
    }
}
