<?php

namespace Gerarodjbaez\Laraplans\Tests\Integration;

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
}
