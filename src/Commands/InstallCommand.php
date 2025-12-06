<?php

namespace Djinson\OpenAiMcp\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    public $signature = 'laravel-mcp:install';

    public $description = 'Install the Laravel MCP package';

    public function handle(): int
    {
        $this->call('vendor:publish', [
            '--tag' => 'config',
            '--provider' => 'Djinson\OpenAiMcp\LaravelMcpServiceProvider',
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'openai-mcp-prompts',
            '--provider' => 'Djinson\OpenAiMcp\LaravelMcpServiceProvider',
        ]);

        $this->info('Laravel MCP installed successfully.');

        return self::SUCCESS;
    }
}
