<?php

namespace Czechbox\LaravelPlans;

use Illuminate\Support\ServiceProvider;
use Czechbox\LaravelPlans\SubscriptionBuilder;
use Czechbox\LaravelPlans\SubscriptionResolver;
use Czechbox\LaravelPlans\Contracts\PlanInterface;
use Czechbox\LaravelPlans\Contracts\PlanFeatureInterface;
use Czechbox\LaravelPlans\Contracts\PlanSubscriptionInterface;
use Czechbox\LaravelPlans\Contracts\SubscriptionBuilderInterface;
use Czechbox\LaravelPlans\Contracts\SubscriptionResolverInterface;
use Czechbox\LaravelPlans\Contracts\PlanSubscriptionUsageInterface;

class LaravelPlansServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'laravelplans');

        //little timestamp trick to make sure migrations are added in the order they are installed and don't cause issues

        $timestamp = date('Y_m_d_His', time());
        $this->publishes([
//            using templates to publish the migrations
            __DIR__ . '/../database/migrations/_create_laravelplans_tables.phpt' => database_path("/migrations/{$timestamp}_create_laravelplans_tables.phpt"),


        ], 'migrations');

        $this->publishes([
            __DIR__ . '/../config/laravelplans.php' => config_path('laravelplans.php')
        ], 'config');

        $this->publishes([
            __DIR__.'/../lang' => resource_path('lang/vendor/laravelplans'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/laravelplans.php', 'laravelplans');

        $this->app->bind(PlanInterface::class, config('laravelplans.models.plan'));
        $this->app->bind(PlanFeatureInterface::class, config('laravelplans.models.plan_feature'));
        $this->app->bind(PlanSubscriptionInterface::class, config('laravelplans.models.plan_subscription'));
        $this->app->bind(PlanSubscriptionUsageInterface::class, config('laravelplans.models.plan_subscription_usage'));
        $this->app->bind(SubscriptionBuilderInterface::class, SubscriptionBuilder::class);
        $this->app->bind(SubscriptionResolverInterface::class, SubscriptionResolver::class);
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
