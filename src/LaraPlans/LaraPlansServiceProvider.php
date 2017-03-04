<?php

namespace Gerardojbaez\LaraPlans;

use Illuminate\Support\ServiceProvider;
use Gerardojbaez\LaraPlans\Contracts\PlanInterface;
use Gerardojbaez\LaraPlans\Contracts\PlanFeatureInterface;
use Gerardojbaez\LaraPlans\Contracts\PlanSubscriptionInterface;
use Gerardojbaez\LaraPlans\Contracts\PlanSubscriptionUsageInterface;

class LaraPlansServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'laraplans');

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations')
        ], 'migrations');

        $this->publishes([
            __DIR__.'/../config/laraplans.php' => config_path('laraplans.php')
        ], 'config');

        $this->publishes([
            __DIR__.'/../lang' => resource_path('lang/vendor/laraplans'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laraplans.php', 'laraplans');

        $this->app->bind(PlanInterface::class, config('laraplans.models.plan'));
        $this->app->bind(PlanFeatureInterface::class, config('laraplans.models.plan_feature'));
        $this->app->bind(PlanSubscriptionInterface::class, config('laraplans.models.plan_subscription'));
        $this->app->bind(PlanSubscriptionUsageInterface::class, config('laraplans.models.plan_subscription_usage'));
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        //
    }
}
