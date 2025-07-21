<?php

namespace Articlai\Articlai;

use Articlai\Articlai\Commands\ArticlaiCommand;
use Articlai\Articlai\Http\Middleware\ArticlaiAuthentication;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ArticlaiServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('articlai-laravel')
            ->hasConfigFile()
            ->hasViews()
            ->hasCommand(ArticlaiCommand::class);
    }

    public function boot()
    {
        parent::boot();

        // Manually publish migrations to ensure correct path resolution
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../database/migrations/create_blogs.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_blogs.php'),
            ], 'articlai-laravel-migrations');
        }
    }

    public function packageBooted(): void
    {
        // Register middleware
        $this->app['router']->aliasMiddleware('articlai.auth', ArticlaiAuthentication::class);

        // Load routes with proper configuration
        Route::group([
            'prefix' => config('articlai-laravel.api.prefix', 'api/articlai'),
            'middleware' => config('articlai-laravel.api.middleware', ['api', 'articlai.auth']),
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        });
    }

    public function packageRegistered(): void
    {
        // Merge package config with application config
        $this->mergeConfigFrom(
            __DIR__.'/../config/articlai-laravel.php',
            'articlai-laravel'
        );

        // Register the main service class
        $this->app->singleton(Articlai::class, function ($app) {
            return new Articlai;
        });

        // Register the facade alias
        $this->app->alias(Articlai::class, 'articlai');
    }

    protected function getPackageBaseDir(): string
    {
        return dirname(__DIR__);
    }
}
