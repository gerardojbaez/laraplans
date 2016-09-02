<?php

namespace Gerarodjbaez\LaraPlans\Tests\Integration\Models;

use Config;
use Carbon\Carbon;
use Gerardojbaez\LaraPlans\Models\Plan;
use Gerardojbaez\LaraPlans\Tests\TestCase;
use Gerardojbaez\LaraPlans\Tests\Models\User;
use Gerardojbaez\LaraPlans\Models\PlanFeature;

class PlanSubscriptionUsageTest extends TestCase
{
    /**
     * Check if usage has expired.
     *
     * @test
     * @return void
     */
    public function it_can_check_if_usage_has_expired()
    {
        Config::set('laraplans.features', [
            'listings_per_month' => [
                'reseteable_interval' => 'month',
                'reseteable_count' => 1
            ]
        ]);

        $plan = Plan::create([
            'name' => 'Pro',
            'description' => 'Pro plan',
            'interval' => 'month'
        ]);

        $plan->features()->saveMany([
            new PlanFeature(['code' => 'listings_per_month', 'value' => 50]),
        ]);

        $user = User::create([
            'email' => 'test@example.org',
            'name' => 'Test user',
            'password' => '123'
        ]);

        $user->newSubscription('main', $plan)->create();

        $usage = $user->subscriptionUsage('main')->record('listings_per_month');

        $this->assertFalse($usage->isExpired());

        $usage->valid_until = Carbon::now()->subDay(); // date is in the past by 1 day...

        $usage->save();

        $this->assertTrue($usage->isExpired());
    }
}
