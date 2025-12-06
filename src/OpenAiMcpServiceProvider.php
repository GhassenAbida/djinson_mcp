<?php

namespace Djinson\OpenAiMcp;

use Illuminate\Support\Facades\File;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Djinson\OpenAiMcp\app\AI\Contracts\ConversationOrchestratorInterface;
use Djinson\OpenAiMcp\app\AI\Contracts\LlmClientInterface;
use Djinson\OpenAiMcp\app\AI\Contracts\QueryFilterInterface;
use Djinson\OpenAiMcp\app\AI\Services\ConversationOrchestrator;
use Djinson\OpenAiMcp\app\AI\LlmClientManager;
use Djinson\OpenAiMcp\app\AI\Services\OpenAiQueryFilter;
use Djinson\OpenAiMcp\app\MCP\Contracts\ToolInterface;
use Djinson\OpenAiMcp\app\MCP\ToolManagerInterface;

class OpenAiMcpServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('openai-mcp')
            ->hasConfigFile('openai-mcp');
    }

    public function registeringPackage(): void
    {
        $this->app->singleton(ConversationOrchestratorInterface::class, ConversationOrchestrator::class);
        $this->app->singleton(LlmClientManager::class, function ($app) {
            return new LlmClientManager($app);
        });
        $this->app->bind(LlmClientInterface::class, function ($app) {
            return $app->make(LlmClientManager::class)->driver();
        });
        $this->app->singleton(QueryFilterInterface::class, OpenAiQueryFilter::class);
    }

    public function bootingPackage(): void
    {
        $this->publishes([
            __DIR__.'/../resources/ai-prompts' => resource_path('ai-prompts'),
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
