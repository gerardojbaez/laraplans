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
    protected $subscription;

    public function setUP()
    {
        parent::setUp();

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

        $user->subscribeToPlan($plan)->save();

        $this->subscription = $user->planSubscription;
    }

    /**
     * Check if usage has expired.
     *
     * @test
     * @return void
     */
    public function it_can_check_if_usage_has_expired()
    {
        $usage = $this->subscription->recordUsage('listings_per_month');

        $this->assertFalse($usage->isExpired());

        $usage->valid_until = (new Carbon)->subDay(); // date is in the past by 1 day...

        $usage->save();

        $this->assertTrue($usage->isExpired());
    }
}
