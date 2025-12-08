# How to Give Claude AI Access to Your Laravel Database

In this tutorial, we will show you how to use `djinson/laravel-mcp` to expose your Laravel application's data to Claude Desktop (or any MCP client) in just a few minutes.

## Prerequisites

- A Laravel application (v10 or v11)
- PHP 8.2+
- Composer

## Step 1: Install the Package

First, install the package via Composer:

```bash
composer require djinson/laravel-mcp
```

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Djinson\OpenAiMcp\LaravelMcpServiceProvider" --tag="config"
```

## Step 2: Create a Tool

We will create a tool that allows the AI to query user statistics from your database. Create a new class `app/MCP/Tools/UserStatsTool.php`:

```php
<?php

namespace App\MCP\Tools;

use Djinson\OpenAiMcp\app\MCP\Contracts\ToolInterface;
use App\Models\User;

class UserStatsTool implements ToolInterface
{
    public function name(): string
    {
        return 'get_user_stats';
    }

    public function description(): string
    {
        return 'Get statistics about the users in the system, optionally filtered by date.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'date_from' => [
                    'type' => 'string',
                    'description' => 'Filter users created after this date (YYYY-MM-DD).',
                ],
            ],
        ];
    }

    public function execute(array $args): array
    {
        $query = User::query();

        if (isset($args['date_from'])) {
            $query->whereDate('created_at', '>=', $args['date_from']);
        }

        return [
            'total_users' => $query->count(),
            'recent_users' => $query->latest()->take(5)->pluck('name'),
        ];
    }
}
```

## Step 3: Configure the MCP Server

Ensure your `config/openai-mcp.php` is set to discover tools in `app/MCP/Tools` (this is the default).

## Step 4: Connect Claude Desktop

You can now run your Laravel app as an MCP server. (Note: This requires an MCP-compatible server runner, or you can expose it via a simple API endpoint that the MCP client connects to).

*Example configuration for Claude Desktop `claude_desktop_config.json`:*

```json
{
  "mcpServers": {
    "laravel-app": {
      "command": "php",
      "args": ["artisan", "mcp:serve"]
    }
  }
}
```

*(Note: The `mcp:serve` command is an example of how you might expose this. Check the package documentation for the exact entry point).*

## Conclusion

You have now successfully exposed your Laravel logic to an AI agent! Claude can now ask "How many users signed up last week?" and your Laravel app will provide the answer accurately.
