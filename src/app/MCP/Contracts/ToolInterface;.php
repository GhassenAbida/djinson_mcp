<?php

namespace App\MCP\Contracts;

use OPGG\LaravelMcpServer\Enums\ProcessMessageType;

/**
 * Defines a single MCP tool.
 */
interface ToolInterface
{
    public function messageType(): ProcessMessageType;

    public function name(): string;

    public function description(): string;

    public function inputSchema(): array;

    public function annotations(): array;

    /**
     * Execute the tool with validated arguments.
     *
     * @param array $arguments
     * @return array
     */
    public function execute(array $arguments): array;
}
