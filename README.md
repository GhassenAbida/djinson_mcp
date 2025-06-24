# djinson/openai-mcp

Laravel integration that wires your Azure OpenAI 4.1 deployment into a Model-Centric Processing (MCP) architecture.

## Features
* Publishes `config/openai-mcp.php` for credentials & endpoints.
* Publishes four prompt stubs to `resources/ai-prompts/`.
* Binds your existing Conversation Orchestrator, LLM Client, and Query Filter contracts.
* Auto-discovers any `ToolInterface` implementations under `app/MCP/Tools`.

## Installation

```bash
composer require djinson/openai-mcp
php artisan vendor:publish --provider="Djinson\OpenAiMcp\OpenAiMcpServiceProvider" --tag="config"
php artisan vendor:publish --provider="Djinson\OpenAiMcp\OpenAiMcpServiceProvider" --tag="openai-mcp-prompts"
