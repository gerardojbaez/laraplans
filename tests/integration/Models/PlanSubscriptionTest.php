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

        $this->user->subscribeToPlan($this->plan)->save();

        $this->subscription = $this->user->planSubscription;
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
        $this->assertTrue($this->subscription->isActive());
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

        $this->assertFalse($this->subscription->isCanceled());
        $this->assertEquals(PlanSubscription::STATUS_ACTIVE, $this->subscription->status);

        // Cancel subscription immediately...
        $this->subscription->cancel(true);

        $this->assertTrue($this->subscription->isCanceled());
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
        $this->subscription->trial_end = (new Carbon)->subDay();
        $this->subscription->setNewPeriod('month', 1, (new Carbon)->subMonths(2));
        $this->assertFalse($this->subscription->isActive());
        $this->assertEquals(PlanSubscription::STATUS_ENDED, $this->subscription->status);

        $this->subscription->trial_end = $this->subscription->trial_end->addDays(2);
        $this->assertTrue($this->subscription->isActive());
        $this->assertEquals(PlanSubscription::STATUS_ACTIVE, $this->subscription->status);
    }

    /**
     * Can check if subscription has ended.
     *
     * @test
     * @return void
     */
    public function period_ended()
    {
        $this->subscription->trial_end = null;
        $this->subscription->setNewPeriod('month', 1, (new Carbon)->subMonths(2));

        $this->assertFalse($this->subscription->isActive());
        $this->assertEquals(PlanSubscription::STATUS_ENDED, $this->subscription->status);
    }

    /**
     * Can record feature usage.
     *
     * @test
     * @return void
     */
    public function it_can_record_feature_usage()
    {
        $usage = $this->subscription->recordUsage('listings_per_month');

        $this->assertInstanceOf(PlanSubscriptionUsage::class, $usage);
    }

    /**
     * Can get feature value.
     *
     * @test
     * @return void
     */
    public function it_can_get_feature_value()
    {
        $this->assertEquals('N', $this->subscription->getFeatureValue('listing_title_bold'));
        $this->assertEquals(30, $this->subscription->getFeatureValue('listing_duration_days'));
    }

    /**
     * Can check if a particular feature is enabled.
     *
     * @test
     * @return void
     */
    public function it_can_check_if_a_feature_is_enabled()
    {
        $this->assertFalse($this->subscription->featureEnabled('listing_title_bold'));
    }

    /**
     * Can check if feature limit was reached.
     *
     * @test
     * @return void
     */
    public function it_can_check_if_feature_limit_was_reached()
    {
        // First, let's test non reached limits...
        $this->assertFalse($this->subscription->limitReached('listings_per_month'));
        $this->assertFalse($this->subscription->limitReached('listing_duration_days'));
        $this->assertFalse($this->subscription->limitReached('listing_title_bold'));

        // Now let's update the usage records to reflect "limit reached" beheavor.
        $this->subscription->recordUsage('listings_per_month', 50);

        $this->assertTrue($this->subscription->limitReached('listings_per_month'));
    }

    /**
     * Can clear usage data.
     *
     * @test
     * @return void
     */
    public function it_can_clear_usage_data()
    {
        $this->subscription->recordUsage('listings_per_month', 2);

        $this->assertEquals(1, $this->subscription->usage->count());

        $this->subscription->clearUsage();

        $this->assertEquals(0, $this->subscription->usage->count());
    }

     /**
     * Can set new period.
     *
     * @test
     * @return void
     */
    public function it_can_set_new_period()
    {
        // Create a subscription that with an ended period...
        $subscription = factory(PlanSubscription::class)->create([
            'plan_id' => factory(Plan::class)->create([
                'interval' => 'month'
            ])->id,
            'trial_end' => (new Carbon)->subMonth(),
            'current_period_start' => (new Carbon)->subMonths(2),
            'current_period_end' => (new Carbon)->subMonth(),
        ]);

        $this->assertFalse($subscription->isActive());

        $subscription->setNewPeriod();

        $expected = new Period('month', 1, $subscription->current_period_start);

        $this->assertTrue($subscription->isActive());
        $this->assertEquals($expected->getStartDate(), $subscription->current_period_start);
        $this->assertEquals($expected->getEndDate(), $subscription->current_period_end);
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
            'trial_end' => (new Carbon)->addDays(10) // End in ten days...
        ]);

        // These are the results that should be returned...
        factory(PlanSubscription::class, 5)->create([
            'trial_end' => (new Carbon)->addDays(3), // Ended a day ago...
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
            'trial_end' => (new Carbon)->addDays(2) // End in two days...
        ]);

        // These are the results that should be returned...
        factory(PlanSubscription::class, 5)->create([
            'trial_end' => (new Carbon)->subDay(), // Ended a day ago...
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
            'current_period_start' => (new Carbon)->subMonth()->addDays(10),
            'current_period_end' => (new Carbon)->addDays(10) // End in ten days...
        ]);

        // These are the results that should be returned...
        factory(PlanSubscription::class, 5)->create([
            'current_period_start' => (new Carbon)->subMonth()->addDays(3),
            'current_period_end' => (new Carbon)->addDays(3), // Ended a day ago...
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
            'current_period_start' => (new Carbon)->subMonth()->addDays(2),
            'current_period_end' => (new Carbon)->addDays(2) // End in two days...
        ]);

        // These are the results that should be returned...
        factory(PlanSubscription::class, 5)->create([
            'current_period_start' => (new Carbon)->subMonth()->addDay(),
            'current_period_end' => (new Carbon)->subDay(), // Ended a day ago...
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
        $this->assertEquals('Business', $this->subscription->plan->name);

        // Let's check if the subscription period was set (i.e., current_period_start
        // and current_period_end)
        $period = new Period($newPlan->interval, $newPlan->interval_count);

        // Expected dates
        $expectedPeriodStartDate = $period->getStartDate();
        $expectedPeriodEndDate = $period->getEndDate();

        // Finaly test period
        $this->assertEquals($expectedPeriodStartDate, $this->subscription->current_period_start);
        $this->assertEquals($expectedPeriodEndDate, $this->subscription->current_period_end);

        // This assertion will make sure that the subscription is now using
        // the new plan features...
        $this->assertEquals('Y', $this->subscription->getFeatureValue('listing_title_bold'));
    }
}
