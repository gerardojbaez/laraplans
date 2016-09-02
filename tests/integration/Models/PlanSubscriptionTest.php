<?php

namespace Gerarodjbaez\LaraPlans\Tests\Integration\Models;

use Config;
use Carbon\Carbon;
use Gerardojbaez\LaraPlans\Period;
use Gerardojbaez\LaraPlans\Models\Plan;
use Gerardojbaez\LaraPlans\Tests\TestCase;
use Gerardojbaez\LaraPlans\Tests\Models\User;
use Gerardojbaez\LaraPlans\Models\PlanFeature;
use Gerardojbaez\LaraPlans\Models\PlanSubscription;
use Gerardojbaez\LaraPlans\Models\PlanSubscriptionUsage;

class PlanSubscriptionTest extends TestCase
{
    protected $plan;
    protected $user;
    protected $subscription;

    /**
     * Setup test
     *
     * @return  void
     */
    public function setUp()
    {
        parent::setUp();

        Config::set('laraplans.features', [
            'listings_per_month' => [
                'reseteable_interval' => 'month',
                'reseteable_count' => 1
            ],
            'pictures_per_listing',
            'listing_duration_days',
            'listing_title_bold'
        ]);

        $this->plan = Plan::create([
            'name' => 'Pro',
            'description' => 'Pro plan',
            'price' => 9.99,
            'interval' => 'month',
            'interval_count' => 1,
            'trial_period_days' => 15,
            'sort_order' => 1,
        ]);

        $this->plan->features()->saveMany([
            new PlanFeature(['code' => 'listings_per_month', 'value' => 50]),
            new PlanFeature(['code' => 'pictures_per_listing', 'value' => 10]),
            new PlanFeature(['code' => 'listing_duration_days', 'value' => 30]),
            new PlanFeature(['code' => 'listing_title_bold', 'value' => 'N']),
        ]);

        $this->user = User::create([
            'email' => 'test@example.org',
            'name' => 'Test user',
            'password' => '123'
        ]);

        $this->user->newSubscription('main', $this->plan)->create();

        $this->subscription = $this->user->subscription('main');
    }

    /**
     * Can get subscription user.
     *
     * @test
     * @return void
     */
    public function it_can_get_subscription_user()
    {
        $this->assertInstanceOf(config('auth.providers.users.model'), $this->subscription->user);
    }

    /**
     * Can check if subscription is active.
     *
     * @test
     * @return void
     */
    public function it_is_active()
    {
        $this->assertTrue($this->subscription->active());
        $this->assertEquals(PlanSubscription::STATUS_ACTIVE, $this->subscription->status);
    }

    /**
     * Can check if subscription is canceled.
     *
     * @test
     * @return void
     */
    public function it_is_canceled()
    {
        // Cancel subscription at period end...
        $this->subscription->cancel();
        $this->subscription->trial_ends_at = null;

        $this->assertTrue($this->subscription->canceled());
        $this->assertTrue($this->subscription->active());
        $this->assertEquals(PlanSubscription::STATUS_ACTIVE, $this->subscription->status);

        // Cancel subscription immediately...
        $this->subscription->cancel(true);

        $this->assertTrue($this->subscription->canceled());
        $this->assertFalse($this->subscription->active());
        $this->assertEquals(PlanSubscription::STATUS_CANCELED, $this->subscription->status);
    }

    /**
     * Can check if subscription is trialling.
     *
     * @test
     * @return void
     */
    public function it_is_trialling()
    {
        // Test if subscription is active after applying a trial.
        $this->subscription->trial_ends_at = $this->subscription->trial_ends_at->addDays(2);
        $this->assertTrue($this->subscription->active());
        $this->assertEquals(PlanSubscription::STATUS_ACTIVE, $this->subscription->status);

        // Test if subscription is inactive after removing the trial.
        $this->subscription->trial_ends_at = Carbon::now()->subDay();
        $this->subscription->cancel(true);
        $this->assertFalse($this->subscription->active());
    }

     /**
     * Can be renewed.
     *
     * @test
     * @return void
     */
    public function it_can_be_renewed()
    {
        // Create a subscription with an ended period...
        $subscription = factory(PlanSubscription::class)->create([
            'plan_id' => factory(Plan::class)->create([
                'interval' => 'month'
            ])->id,
            'trial_ends_at' => Carbon::now()->subMonth(),
            'ends_at' => Carbon::now()->subMonth(),
        ]);

        $this->assertFalse($subscription->active());

        $subscription->renew();

        $this->assertTrue($subscription->active());
        $this->assertEquals(Carbon::now()->addMonth(), $subscription->ends_at);
    }

    /**
     * Can find subscription with an ending trial.
     *
     * @test
     * @return void
     */
    public function it_can_find_subscriptions_with_ending_trial()
    {
        // For "control", these subscription shouldn't be
        // included in the result...
        factory(PlanSubscription::class, 10)->create([
            'trial_ends_at' => Carbon::now()->addDays(10) // End in ten days...
        ]);

        // These are the results that should be returned...
        factory(PlanSubscription::class, 5)->create([
            'trial_ends_at' => Carbon::now()->addDays(3), // Ended a day ago...
        ]);

        $result = PlanSubscription::FindEndingTrial(3)->get();

        $this->assertEquals(5, $result->count());
    }

    /**
     * Can find subscription with an ended trial.
     *
     * @test
     * @return void
     */
    public function it_can_find_subscriptions_with_ended_trial()
    {
        // For "control", these subscription shouldn't be
        // included in the result...
        factory(PlanSubscription::class, 10)->create([
            'trial_ends_at' => Carbon::now()->addDays(2) // End in two days...
        ]);

        // These are the results that should be returned...
        factory(PlanSubscription::class, 5)->create([
            'trial_ends_at' => Carbon::now()->subDay(), // Ended a day ago...
        ]);

        $result = PlanSubscription::FindEndedTrial()->get();

        $this->assertEquals(5, $result->count());
    }

    /**
     * Can find subscription with an ending period.
     *
     * @test
     * @return void
     */
    public function it_can_find_subscriptions_with_ending_period()
    {
        // For "control", these subscription shouldn't be
        // included in the result...
        factory(PlanSubscription::class, 10)->create([
            'ends_at' => Carbon::now()->addDays(10) // End in ten days...
        ]);

        // These are the results that should be returned...
        factory(PlanSubscription::class, 5)->create([
            'ends_at' => Carbon::now()->addDays(3), // Ended a day ago...
        ]);

        $result = PlanSubscription::FindEndingPeriod(3)->get();

        $this->assertEquals(5, $result->count());
    }

    /**
     * Can find subscription with an ended period.
     *
     * @test
     * @return void
     */
    public function it_can_find_subscriptions_with_ended_period()
    {
        // For "control", these subscription shouldn't be
        // included in the result...
        factory(PlanSubscription::class, 10)->create([
            'ends_at' => Carbon::now()->addDays(2) // End in two days...
        ]);

        // These are the results that should be returned...
        factory(PlanSubscription::class, 5)->create([
            'ends_at' => Carbon::now()->subDay(), // Ended a day ago...
        ]);

        $result = PlanSubscription::FindEndedPeriod()->get();

        $this->assertEquals(5, $result->count());
    }

    /**
     * Can change subscription plan.
     *
     * @test
     * @return void
     */
    public function it_can_change_plan()
    {
        $newPlan = Plan::create([
            'name' => 'Business',
            'description' => 'Business plan',
            'price' => 49.89,
            'interval' => 'month',
            'interval_count' => 1,
            'trial_period_days' => 30,
            'sort_order' => 1,
        ]);

        $newPlan->features()->saveMany([
            new PlanFeature(['code' => 'listing_title_bold', 'value' => 'Y']),
        ]);

        // Change plan
        $this->subscription->changePlan($newPlan)->save();

        // Plan was changed?
        $this->assertEquals('Business', $this->subscription->fresh()->plan->name);

        // Let's check if the subscription period was set
        $period = new Period($newPlan->interval, $newPlan->interval_count);

        // Expected dates
        $expectedPeriodStartDate = $period->getStartDate();
        $expectedPeriodEndDate = $period->getEndDate();

        // Finaly test period
        $this->assertEquals($expectedPeriodEndDate, $this->subscription->ends_at);

        // This assertion will make sure that the subscription is now using
        // the new plan features...
        $this->assertEquals('Y', $this->subscription->fresh()->ability()->value('listing_title_bold'));
    }
}
