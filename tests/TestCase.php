<?php

namespace Articlai\Articlai\Tests;

use Articlai\Articlai\ArticlaiServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Articlai\\Articlai\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            ArticlaiServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set up ArticlAI configuration for testing
        config()->set('articlai-laravel.auth.method', 'api_key');
        config()->set('articlai-laravel.auth.api_key', 'test-api-key');
        config()->set('articlai-laravel.content.sanitize_html', true);
        config()->set('articlai-laravel.platform.name', 'Test Platform');
        config()->set('articlai-laravel.platform.version', '1.0.0');

        // Run the migration
        $migration = include __DIR__.'/../database/migrations/create_blogs.php.stub';
        $migration->up();
    }
}
