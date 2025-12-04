<?php

namespace Gerardojbaez\Laraplans\Tests;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as TestBenchCase;

class TestCase extends TestBenchCase
{
    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(realpath(__DIR__ . '/../database/migrations'));
        $this->loadMigrationsFrom(realpath(__DIR__ . '/../workbench/database/migrations'));

        // Run package migrations
        $this->artisan('migrate', [
            '--database' => 'testbench',
        ]);

        // These migrations are for testing purposes only...
        $this->artisan('migrate', [
            '--database' => 'testbench',
        ]);
    }

    /**
     * Define environment setup.
     *
     * @param  Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app): void
    {
        // Set the user model
        $app['config']->set('auth.providers.users.model', '\App\Models\User');

        // set up database configuration
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    /**
     * Get Laraplans package service provider.
     *
     * @param  Application $app
     * @return array
     */
    public function getPackageProviders($app): array
    {
        return ['Gerardojbaez\Laraplans\LaraplansServiceProvider'];
    }
}