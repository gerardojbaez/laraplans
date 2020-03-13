<?php

namespace Czechbox\Laraplans\Tests;

use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use Orchestra\Testbench\TestCase as Testbench;
use Illuminate\Database\Eloquent\Factory as EloquentFactory;

class TestCase extends Testbench
{
    /**
     * Setup the test enviroment.
     *
     * @return void
     */
    public function setUp() : void
    {
        parent::setUp();

        // // Run package migrations
        // $this->artisan('migrate', [
        //     '--database' => 'testbench',
        //     '--realpath' => realpath(__DIR__.'/../src/database/migrations'),
        // ]);
        $this->loadMigrationsFrom(__DIR__ . '/../src/database/migrations');
        $this->loadMigrationsFrom(__DIR__ . '/migrations');
        $this->artisan('migrate', ['--database' => 'testbench'])->run();
        // $this->loadLaravelMigrations();
        // These migrations are for testing purposes only...

    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $this->registerEloquentFactory($app);

        // Set user model
        $app['config']->set('auth.providers.users.model', '\Czechbox\Laraplans\Tests\Models\User');

        // set up database configuration
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
          'driver'   => 'sqlite',
          'database' => ':memory:',
          'prefix'   => '',
        ]);
    }

    /**
     * Get Laraplans package service provider.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return array
     */
    public function getPackageProviders($app)
    {
        return ['Czechbox\Laraplans\LaraplansServiceProvider'];
    }

    /**
     * Register the Eloquent factory instance in the container.
     *
     * @return void
     */
    protected function registerEloquentFactory($app)
    {
        $app->singleton(FakerGenerator::class, function () {
            return FakerFactory::create();
        });

        $app->singleton(EloquentFactory::class, function ($app) {
            $faker = $app->make(FakerGenerator::class);

            return EloquentFactory::construct($faker, __DIR__.'/../src/database/factories');
        });
    }
}
