<?php

declare(strict_types=1);

namespace AshiqueAr\LaravelCrudGenerator\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use AshiqueAr\LaravelCrudGenerator\CrudGeneratorServiceProvider;

/**
 * Base test case for the Laravel CRUD Generator package.
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
    }

    /**
     * Get package providers.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array<string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            CrudGeneratorServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function defineEnvironment($app): void
    {
        // Setup the database
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Configure authentication
        $app['config']->set('auth.defaults.guard', 'web');
        $app['config']->set('auth.guards.web', [
            'driver' => 'session',
            'provider' => 'users',
        ]);

        // Set up CRUD configuration
        $app['config']->set('crud', [
            'route_prefix' => 'api/crud',
            'middleware' => ['api'],
            'default_per_page' => 15,
            'max_per_page' => 100,
            'resources' => [
                // Test resources will be added by individual tests
            ],
        ]);
    }

    /**
     * Set up the database for testing.
     */
    protected function setUpDatabase(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}


