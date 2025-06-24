<?php

use Spatie\LaravelPackageTools\Package;

return static function (Package $package): void {
    $package
        ->name('openai-mcp')
        ->hasConfigFile('openai-mcp')
        ->hasPublishableAssets(['resources/ai-prompts']);
};
