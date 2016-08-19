<?php

namespace Gerarodjbaez\LaraPlans\Tests\Integration\Models;

use Gerardojbaez\LaraPlans\Models\Plan;
use Gerardojbaez\LaraPlans\Tests\TestCase;
use Gerardojbaez\LaraPlans\Tests\Models\User;
use Gerardojbaez\LaraPlans\Models\PlanFeature;

class UserTest extends TestCase
{
    protected $plan;
    protected $user;

    public function setUp()
    {
        parent::setUp();

        $this->plan = Plan::create([
            'name' => 'Pro',
            'description' => 'Pro plan',
            'code' => 'pro',
            'price' => 9.99,
            'interval' => 'month',
            'interval_count' => 1,
            'trial_period_days' => 15,
            'sort_order' => 1,
        ]);

        $this->plan->features()->saveMany([
            new PlanFeature(['code' => 'listings_per_month', 'value' => 50, 'sort_order' => 1]),
            new PlanFeature(['code' => 'pictures_per_listing', 'value' => 10, 'sort_order' => 5]),
            new PlanFeature(['code' => 'listing_duration_days', 'value' => 30, 'sort_order' => 10]),
        ]);

        $this->user = User::create([
            'email' => 'test@example.org',
            'name' => 'Test user',
            'password' => '123'
        ]);
    }

    /**
     * Can subscribe user to a plan.
     *
     * @test
     * @return void
     */
    public function it_can_subscribe_user_to_a_plan()
    {
        // Subscribe user to plan
        $saved = $this->user->subscribeToPlan($this->plan->id)->save();

        $this->assertTrue($saved);
        $this->assertEquals('Pro', $this->user->plan->name);

        return $this->user;
    }

    /**
     * New subscription must have a trial end date when plan has trial defined.
     *
     * @depends it_can_subscribe_user_to_a_plan
     * @test
     * @return void
     */
    public function new_subscription_has_trial_when_trial_is_defined($user)
    {
        $this->assertTrue(is_null($user->planSubscription->trial_end) === false);
    }
}