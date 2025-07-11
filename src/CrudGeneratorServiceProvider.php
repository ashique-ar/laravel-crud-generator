<?php

declare(strict_types=1);

namespace AshiqueAr\LaravelCrudGenerator;

use AshiqueAr\LaravelCrudGenerator\Console\Commands\GenerateCrudPermissions;
use AshiqueAr\LaravelCrudGenerator\Console\Commands\InstallCrudGenerator;
use AshiqueAr\LaravelCrudGenerator\Console\Commands\MakeCrudLogic;
use AshiqueAr\LaravelCrudGenerator\Console\Commands\MakeCrudResource;
use AshiqueAr\LaravelCrudGenerator\Http\Middleware\CheckCrudPermission;
use AshiqueAr\LaravelCrudGenerator\Services\CrudManager;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider for Laravel CRUD Generator Package
 *
 * Bootstraps the package by registering services, middleware, commands,
 * and publishing configuration files.
 */
class CrudGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge package configuration
        $configFile = __DIR__.'/../config/crud.php';
        $defaultConfig = require $configFile;
        $existingConfig = $this->app['config']->get('crud', []);
        if (! is_array($existingConfig)) {
            $existingConfig = [];
        }
        $this->app['config']->set('crud', array_merge($defaultConfig, $existingConfig));

        // Register singleton for CRUD manager
        $this->app->singleton('crud-generator', function ($app) {
            return new CrudManager;
        });

        // Bind the CRUD manager to the container
        $this->app->bind(CrudManager::class, function ($app) {
            return $app['crud-generator'];
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register middleware
        $this->registerMiddleware();

        // Register console commands
        $this->registerCommands();

        // Register routes
        $this->registerRoutes();

        // Publish configuration and stubs
        $this->registerPublishables();
    }

    /**
     * Register middleware with the application.
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];

        $router->aliasMiddleware('check.crud.permission', CheckCrudPermission::class);
    }

    /**
     * Register console commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateCrudPermissions::class,
                MakeCrudLogic::class,
                MakeCrudResource::class,
                InstallCrudGenerator::class,
            ]);
        }
    }

    /**
     * Register package routes.
     */
    protected function registerRoutes(): void
    {
        // Routes will be registered dynamically by the CrudManager
        // This allows for flexible route registration based on configuration
    }

    /**
     * Register publishable assets.
     */
    protected function registerPublishables(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish configuration file
            $this->publishes([
                __DIR__.'/../config/crud.php' => config_path('crud.php'),
            ], ['crud-config', 'config']);

            // Publish stub files
            $this->publishes([
                __DIR__.'/../resources/stubs' => resource_path('stubs'),
            ], 'crud-stubs');

            // Publish all assets
            $this->publishes([
                __DIR__.'/../config/crud.php' => config_path('crud.php'),
                __DIR__.'/../resources/stubs' => resource_path('stubs'),
            ], 'crud-generator');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [
            'crud-generator',
            CrudManager::class,
        ];
    }
}
