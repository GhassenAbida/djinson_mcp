<?php

namespace Djinson\OpenAiMcp;

use Illuminate\Support\Facades\File;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Djinson\OpenAiMcp\app\AI\Contracts\ConversationOrchestratorInterface;
use Djinson\OpenAiMcp\app\AI\Contracts\LlmClientInterface;
use Djinson\OpenAiMcp\app\AI\Contracts\QueryFilterInterface;
use Djinson\OpenAiMcp\app\AI\ConversationOrchestrator;
use Djinson\OpenAiMcp\app\AI\LlmClientManager;
use Djinson\OpenAiMcp\app\AI\OpenAiQueryFilter;
use Djinson\OpenAiMcp\app\MCP\Contracts\ToolInterface;
use Djinson\OpenAiMcp\app\MCP\Contracts\ToolManagerInterface;
use Djinson\OpenAiMcp\app\MCP\ToolManager;
use Djinson\OpenAiMcp\Commands\InstallCommand;

class LaravelMcpServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-mcp')
            ->hasConfigFile('openai-mcp')
            ->hasCommand(InstallCommand::class);
    }

    public function registeringPackage(): void
    {
        // dd('Provider Loaded');
        $this->app->singleton(ConversationOrchestratorInterface::class, ConversationOrchestrator::class);
        $this->app->singleton(LlmClientManager::class, function ($app) {
            return new LlmClientManager($app);
        });
        $this->app->bind(LlmClientInterface::class, function ($app) {
            return $app->make(LlmClientManager::class)->driver();
        });
        $this->app->singleton(QueryFilterInterface::class, OpenAiQueryFilter::class);
        $this->app->singleton(ToolManagerInterface::class, ToolManager::class);
    }

    public function bootingPackage(): void
    {
        $this->publishes([
            __DIR__.'/resources/ai-prompts' => resource_path('ai-prompts'),
        ], 'openai-mcp-prompts');

        $this->discoverAndTagTools();
    }

    private function discoverAndTagTools(): void
    {
        $path      = app_path('MCP/Tools');
        $namespace = app()->getNamespace().'MCP\\Tools\\';

        if (! File::isDirectory($path)) {
            return;
        }

        foreach (File::files($path) as $file) {
            $class = $namespace.$file->getFilenameWithoutExtension();

            if (class_exists($class) && is_subclass_of($class, ToolInterface::class)) {
                // Tag the tool so the consumer’s ToolManager can pull them in.
                $this->app->tag($class, 'mcp_tool');
            }
        }
    }
}
