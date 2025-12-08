<?php

namespace Djinson\OpenAiMcp\Tests\Unit;

use Djinson\OpenAiMcp\app\AI\ConversationOrchestrator;
use Djinson\OpenAiMcp\app\AI\Contracts\LlmClientInterface;
use Djinson\OpenAiMcp\app\MCP\Contracts\ToolManagerInterface;
use Djinson\OpenAiMcp\app\MCP\Contracts\ToolInterface;
use Djinson\OpenAiMcp\Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Mockery;

class ConversationOrchestratorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('openai-mcp.paths.prompts', __DIR__ . '/../../src/resources/ai-prompts');
        // Mock file reads for prompts to avoid actual file system dependency in tests if possible,
        // but since we set the path to real files, we can rely on them or mock File facade.
        // For simplicity, let's mock File facade to avoid path issues.
        File::shouldReceive('get')->andReturn('prompt content');
    }

    public function test_it_stops_after_max_steps()
    {
        Config::set('openai-mcp.retries.max_steps', 2);

        $client = Mockery::mock(LlmClientInterface::class);
        $tools = Mockery::mock(ToolManagerInterface::class);
        $tool = Mockery::mock(ToolInterface::class);

        // Initial call returns a function call
        $client->shouldReceive('call')
            ->once()
            ->andReturn([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'function_call' => ['name' => 'test_tool', 'arguments' => '{}']
                    ]
                ]]
            ]);

        // Tool execution
        $tools->shouldReceive('get')->with('test_tool')->andReturn($tool);
        $tool->shouldReceive('execute')->andReturn(['result' => 'val1']);

        // Second call (step 1) returns another function call
        $client->shouldReceive('call')
            ->once()
            ->andReturn([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'function_call' => ['name' => 'test_tool', 'arguments' => '{"a":1}']
                    ]
                ]]
            ]);
        
        // Tool execution 2
        $tools->shouldReceive('get')->with('test_tool')->andReturn($tool);
        $tool->shouldReceive('execute')->andReturn(['result' => 'val2']);

        // Third call (step 2) - should trigger max steps check before this if logic is right?
        // Wait, logic is: while(isset(function_call)) { if steps >= max ... }
        // So:
        // Initial call -> returns FC. Loop starts. steps=0. < max(2).
        // Execute tool.
        // Call LLM -> returns FC. Loop continues. steps=1. < max(2).
        // Execute tool.
        // Call LLM -> returns FC. Loop continues. steps=2. >= max(2). BREAK.
        
        // So we need the LLM to return a FC 3 times to hit the limit of 2 steps?
        // No, step count increments at start of loop.
        // 1. Initial LLM resp has FC. Enter loop.
        // 2. steps check (0 >= 2) False. steps becomes 1.
        // 3. Execute tool.
        // 4. Call LLM (2nd time). Returns FC.
        // 5. Loop. steps check (1 >= 2) False. steps becomes 2.
        // 6. Execute tool.
        // 7. Call LLM (3rd time). Returns FC.
        // 8. Loop. steps check (2 >= 2) True. BREAK.
        
        $client->shouldReceive('call')
            ->once()
            ->andReturn([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'function_call' => ['name' => 'test_tool', 'arguments' => '{"a":2}']
                    ]
                ]]
            ]);

        // Final call for summary
        $client->shouldReceive('call')
            ->once()
            ->with(Mockery::on(function($messages) {
                return end($messages)['role'] === 'system' && str_contains(end($messages)['content'], 'Maximum conversation steps reached');
            }), [], 'none')
            ->andReturn([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Max steps reached summary'
                    ]
                ]]
            ]);

        $orchestrator = new ConversationOrchestrator($client, $tools);
        $result = $orchestrator->orchestrateConversation('hello');

        $this->assertEquals('Max steps reached summary', $result);
    }

    public function test_it_detects_loops_and_feeds_back_error()
    {
        $client = Mockery::mock(LlmClientInterface::class);
        $tools = Mockery::mock(ToolManagerInterface::class);

        // Initial call returns FC
        $client->shouldReceive('call')
            ->once()
            ->andReturn([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'function_call' => ['name' => 'test_tool', 'arguments' => '{"a":1}']
                    ]
                ]]
            ]);

        // Tool execution 1
        $tool = Mockery::mock(ToolInterface::class);
        $tools->shouldReceive('get')->with('test_tool')->andReturn($tool);
        $tool->shouldReceive('execute')->andReturn(['result' => 'val']);

        // Second call returns SAME FC
        $client->shouldReceive('call')
            ->once()
            ->andReturn([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'function_call' => ['name' => 'test_tool', 'arguments' => '{"a":1}']
                    ]
                ]]
            ]);

        // Expect orchestrator to NOT execute tool, but send error back to LLM
        $client->shouldReceive('call')
            ->once()
            ->with(Mockery::on(function($messages) {
                $lastMsg = end($messages);
                return $lastMsg['role'] === 'function' 
                    && $lastMsg['name'] === 'test_tool'
                    && str_contains($lastMsg['content'], 'Cycle detected');
            }), Mockery::any())
            ->andReturn([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Apology for loop'
                    ]
                ]]
            ]);

        $orchestrator = new ConversationOrchestrator($client, $tools);
        $result = $orchestrator->orchestrateConversation('hello');

        $this->assertEquals('Apology for loop', $result);
    }
}
