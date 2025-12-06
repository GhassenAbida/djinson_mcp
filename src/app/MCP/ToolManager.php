<?php

namespace Djinson\OpenAiMcp\app\MCP;

use Djinson\OpenAiMcp\app\MCP\Contracts\ToolInterface;
use Djinson\OpenAiMcp\app\MCP\Contracts\ToolManagerInterface;

class ToolManager implements ToolManagerInterface
{
    protected array $tools = [];

    public function register(string $name, ToolInterface $tool): void
    {
        $this->tools[$name] = $tool;
    }

    public function get(string $name): ToolInterface
    {
        if (! isset($this->tools[$name])) {
            throw new \InvalidArgumentException("Tool [{$name}] not found.");
        }

        return $this->tools[$name];
    }

    public function all(): array
    {
        return $this->tools;
    }
}
