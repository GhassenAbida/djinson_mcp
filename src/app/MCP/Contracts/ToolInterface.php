<?php

namespace Djinson\OpenAiMcp\app\MCP\Contracts;

interface ToolInterface
{
    public function name(): string;
    public function description(): string;
    public function execute(array $args): array;
    public function parameters(): array;
}
