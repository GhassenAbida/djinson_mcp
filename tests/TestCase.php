<?php

namespace Djinson\OpenAiMcp\Tests;

use Djinson\OpenAiMcp\LaravelMcpServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    /**
     * The latest response from the command.
     *
     * @var \Illuminate\Testing\TestResponse|null
     */
    public static $latestResponse = null;

    protected function getPackageProviders($app)
    {
        return [
            LaravelMcpServiceProvider::class,
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
