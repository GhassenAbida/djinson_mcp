<?php

namespace App\MCP\Contracts;

/**
 * Manages registration and retrieval of MCP tools.
 */
interface ToolManagerInterface
{
    /**
     * Register a Tool instance under its name.
     */
    public function register(string $name, ToolInterface $tool): void;

    /**
     * Retrieve a registered Tool by name.
     *
     * @throws \InvalidArgumentException
     */
    public function get(string $name): ToolInterface;

    /**
     * Get all registered tools (name => instance).
     *
     * @return ToolInterface[]
     */
    public function all(): array;
}
