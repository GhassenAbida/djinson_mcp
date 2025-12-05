<?php

namespace Djinson\OpenAiMcp\Tests;

use Djinson\OpenAiMcp\OpenAiMcpServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            OpenAiMcpServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_openai-mcp_table.php.stub';
        $migration->up();
        */
    }
}
