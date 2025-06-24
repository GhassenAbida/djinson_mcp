<?php

namespace Djinson\OpenAiMcp;

use Illuminate\Support\Facades\File;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use App\AI\Contracts\ConversationOrchestratorInterface;
use App\AI\Contracts\LlmClientInterface;
use App\AI\Contracts\QueryFilterInterface;
use App\AI\ConversationOrchestrator;
use App\AI\AzureLlmClient;
use App\AI\OpenAiQueryFilter;
use App\MCP\Contracts\ToolInterface;
use App\MCP\ToolManagerInterface;

class OpenAiMcpServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('openai-mcp')
            ->hasConfigFile('openai-mcp')
            ->hasPublishableAssets([
                __DIR__.'/../resources/ai-prompts' => resource_path('ai-prompts'),
            ], 'openai-mcp-prompts');
    }

    public function registeringPackage(): void
    {
        $this->app->singleton(ConversationOrchestratorInterface::class, ConversationOrchestrator::class);
        $this->app->singleton(LlmClientInterface::class, AzureLlmClient::class);
        $this->app->singleton(QueryFilterInterface::class, OpenAiQueryFilter::class);
    }

    public function bootingPackage(): void
    {
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
