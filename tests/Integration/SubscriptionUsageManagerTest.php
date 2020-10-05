<?php

namespace Gerarodjbaez\Laraplans\Tests\Integration;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Gerardojbaez\Laraplans\Models\Plan;
use Gerardojbaez\Laraplans\Tests\TestCase;
use Gerardojbaez\Laraplans\Tests\Models\User;
use Gerardojbaez\Laraplans\Models\PlanFeature;
use Gerardojbaez\Laraplans\SubscriptionUsageManger;
use Gerardojbaez\Laraplans\Models\PlanSubscriptionUsage;

class SubscriptionUsageMangerTest extends TestCase
{
    /**
     * Can subscription features usage.
     *
     * @test
     * @return void
     */
    public function it_can_record_usage()
    {
        $user = User::create([
            'email' => 'gerardo@email.dev',
            'name' => 'Gerardo',
            'password' => 'password'
        ]);

        $plan = Plan::create([
            'name' => 'Pro',
            'description' => 'Pro plan',
            'price' => 9.99,
            'interval' => 'month',
            'interval_count' => 1,
            'trial_period_days' => 15,
        ]);

        $plan->features()->saveMany([
            new PlanFeature(['code' => 'SAMPLE_SIMPLE_FEATURE', 'value' => 5])
        ]);

        $user->newSubscription('main', $plan)->create();

        // Record usage
        $usage = $user->subscriptionUsage('main')->record('SAMPLE_SIMPLE_FEATURE')->fresh();

        $this->assertEquals(1, $user->subscriptions->count());
        $this->assertInstanceOf(PlanSubscriptionUsage::class, $usage);
        $this->assertEquals(1, $usage->used);
        $this->assertEquals(4, $user->fresh()->subscription('main')->ability()->remainings('SAMPLE_SIMPLE_FEATURE'));

        // Record usage by custom incremental amount
        $usage = $user->subscriptionUsage('main')->record('SAMPLE_SIMPLE_FEATURE', 2)->fresh();
        $this->assertInstanceOf(PlanSubscriptionUsage::class, $usage);
        $this->assertEquals(3, $usage->used);
        $this->assertEquals(2, $user->fresh()->subscription('main')->ability()->remainings('SAMPLE_SIMPLE_FEATURE'));

        // Record usage by fixed amount
        $usage = $user->subscriptionUsage('main')->record('SAMPLE_SIMPLE_FEATURE', 2, false)->fresh();
        $this->assertInstanceOf(PlanSubscriptionUsage::class, $usage);
        $this->assertEquals(2, $usage->used);
        $this->assertEquals(3, $user->fresh()->subscription('main')->ability()->remainings('SAMPLE_SIMPLE_FEATURE'));

        // Reduce uses
        $usage = $user->subscriptionUsage('main')->reduce('SAMPLE_SIMPLE_FEATURE')->fresh();
        $this->assertEquals(1, $usage->used);
        $this->assertEquals(4, $user->fresh()->subscription('main')->ability()->remainings('SAMPLE_SIMPLE_FEATURE'));

        // Clear usage
        $user->subscriptionUsage('main')->clear();
        $this->assertEquals(0, $user->subscription('main')->usage()->count());
        $this->assertEquals(5, $user->fresh()->subscription('main')->ability()->remainings('SAMPLE_SIMPLE_FEATURE'));
    }

    /**
     * Can reset subscription feature usage with dates.
     *
     * @test
     */
    public function it_can_reset_subscription_feature_usage() {

        Carbon::setTestNow(Carbon::create(2016, 12, 21, 12));

        Config::set('laraplans.features', [
            'SAMPLE_SIMPLE_FEATURE' => [
                'resettable_interval' => 'month',
                'resettable_count' => 1,
            ],
        ]);

        $user = User::create([
            'email' => 'gerardo@email.dev',
            'name' => 'Gerardo',
            'password' => 'password',
        ]);

        $plan = Plan::create([
            'name' => 'Pro',
            'description' => 'Pro plan',
            'price' => 999,
            'currency' => 'USD',
            'interval' => 'year',
            'interval_count' => 1,
            'trial_period_days' => 15,
        ]);

        $plan->features()->saveMany([
            new PlanFeature(['code' => 'SAMPLE_SIMPLE_FEATURE', 'value' => 5]),
        ]);

        $user->newSubscription('main', $plan)->create();

        // Refresh user
        $user->refresh();

        // Record usage by custom incremental amount
        $usage = $user->subscriptionUsage('main')->record('SAMPLE_SIMPLE_FEATURE', 2)->fresh();
        $this->assertInstanceOf(PlanSubscriptionUsage::class, $usage);
        $this->assertEquals(Carbon::create(2017, 01, 21, 12), $usage->valid_until);
        $this->assertEquals(2, $usage->used);
        $this->assertEquals(3, $user->fresh()->subscription('main')->ability()->remainings('SAMPLE_SIMPLE_FEATURE'));

        // Time travel 4 months from now
        Carbon::setTestNow(Carbon::create(2017, 04, 05, 12));

        // Record usage by custom incremental amount
        $usage = $user->subscriptionUsage('main')->record('SAMPLE_SIMPLE_FEATURE', 2)->fresh();
        $this->assertInstanceOf(PlanSubscriptionUsage::class, $usage);
        $this->assertEquals(Carbon::create(2017, 04, 21, 12), $usage->valid_until);
        $this->assertEquals(2, $usage->used);
        $this->assertEquals(3, $user->fresh()->subscription('main')->ability()->remainings('SAMPLE_SIMPLE_FEATURE'));
    }
}
