# djinson/laravel-mcp

Laravel integration that wires your Azure OpenAI 4.1, OpenAI, or Google Gemini deployment into a Model-Centric Processing (MCP) architecture.

## Requirements
*   PHP: ^8.2
*   Laravel: ^10.0 or ^11.0

## Features
*   **Multi-Provider Support**: Supports Azure OpenAI, standard OpenAI, and Google Gemini.
*   **Centralized Configuration**: Publishes `config/openai-mcp.php` for managing credentials, endpoints, model options, retries, and timeouts.
*   **Robust Error Handling**: Custom `LlmException` for specific error handling and structured logging for better observability.
*   **Conversation Orchestration**: Manages multi-turn conversations, tool execution, and cycle detection.
*   **Prompt Management**: Publishes prompt stubs to `resources/ai-prompts/`.
*   **Tool Auto-Discovery**: Automatically discovers and registers `ToolInterface` implementations under `app/MCP/Tools`.
*   **Testing Support**: Includes a test suite using `orchestra/testbench` for verification.

## Installation

```bash
composer require djinson/laravel-mcp
```

### Publish Configuration
Publish the configuration file to `config/openai-mcp.php`:
```bash
php artisan vendor:publish --provider="Djinson\OpenAiMcp\OpenAiMcpServiceProvider" --tag="config"
```

### Publish Prompts
Publish the default AI prompts to `resources/ai-prompts/`:
```bash
php artisan vendor:publish --provider="Djinson\OpenAiMcp\OpenAiMcpServiceProvider" --tag="openai-mcp-prompts"
```

## Configuration
Configure your Azure OpenAI credentials and settings in your `.env` file:

```env
AZURE_OPENAI_KEY=your-api-key
AZURE_OPENAI_ENDPOINT=https://your-resource-name.openai.azure.com
AZURE_OPENAI_DEPLOYMENT=gpt-4
AZURE_OPENAI_API_VERSION=2024-02-15-preview
```

You can also customize retries, timeouts, and model parameters in `config/openai-mcp.php`.

## Testing
To run the package tests, you can use `phpunit` or `pest`. If you are on Windows, it is recommended to run tests via WSL.

```bash
composer install
vendor/bin/phpunit
```
