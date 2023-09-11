<?php

// @codingStandardsIgnoreFile

namespace Gerarodjbaez\Laraplans\Tests\Integration\Models;

use Carbon\Carbon;
use Gerardojbaez\Laraplans\Events\SubscriptionCanceled;
use Gerardojbaez\Laraplans\Events\SubscriptionCreated;
use Gerardojbaez\Laraplans\Events\SubscriptionPlanChanged;
use Gerardojbaez\Laraplans\Events\SubscriptionRenewed;
use Gerardojbaez\Laraplans\Models\Plan;
use Gerardojbaez\Laraplans\Models\PlanFeature;
use Gerardojbaez\Laraplans\Models\PlanSubscription;
use Gerardojbaez\Laraplans\Period;
use Gerardojbaez\Laraplans\Tests\Models\User;
use Gerardojbaez\Laraplans\Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;

class PlanSubscriptionTest extends TestCase
{
    protected $plan;

    protected $user;

    protected $subscription;

    /**
     * Setup test
     */
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('laraplans.features', [
            'listings_per_month' => [
                'resettable_interval' => 'month',
                'resettable_count' => 1,
            ],
            'pictures_per_listing',
            'listing_duration_days',
            'listing_title_bold',
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
            'password' => '123',
        ]);

        $this->user->newSubscription('main', $this->plan)->create();

        $this->subscription = $this->user->subscription('main');
    }

    /** @test */
    public function it_triggers_create_event_when_created()
    {
        // Arrange
        Event::fake();
        $this->withoutExceptionHandling();
        // Act
        $subscription = PlanSubscription::factory()->create();
        // Assert
        Event::assertDispatched(SubscriptionCreated::class, function ($event) use ($subscription) {
            return (int) $event->subscription->id === (int) $subscription->id;
        });
    }

    /**
     * Can get subscription user.
     *
     * @test
     *
     * @return void
     */
    public function it_gets_subscribable_model_instance()
    {
        $this->assertInstanceOf(User::class, $this->subscription->subscribable);
    }

    /**
     * Can check if subscription is active.
     *
     * @test
     *
     * @return void
     */
    public function it_determines_if_is_active()
    {
        $this->assertTrue($this->subscription->isActive());
        $this->assertEquals(PlanSubscription::STATUS_ACTIVE, $this->subscription->status);
    }

    /**
     * Can check if subscription is canceled.
     *
     * @test
     *
     * @return void
     */
    public function it_cancels()
    {
        Event::fake();

        // Cancel subscription at period end...
        $this->subscription->cancel();
        $this->subscription->trial_ends_at = null;

        $this->assertTrue($this->subscription->isCanceled());
        $this->assertFalse($this->subscription->isCanceledImmediately());
        $this->assertTrue($this->subscription->isActive());
        $this->assertEquals(PlanSubscription::STATUS_ACTIVE, $this->subscription->status);

        // Cancel subscription immediately...
        $this->subscription->cancel(true);

        $this->assertTrue($this->subscription->isCanceled());
        $this->assertTrue($this->subscription->isCanceledImmediately());
        $this->assertFalse($this->subscription->isActive());
        $this->assertEquals(PlanSubscription::STATUS_CANCELED, $this->subscription->status);

        $subscription = $this->subscription;

        Event::assertDispatched(SubscriptionCanceled::class, function ($event) use ($subscription) {
            return (int) $event->subscription->id === (int) $subscription->id;
        });
    }

    /**
     * Can check if subscription is trialling.
     *
     * @test
     *
     * @return void
     */
    public function it_determines_if_is_trialling()
    {
        // Test if subscription is active after applying a trial.
        $this->subscription->trial_ends_at = $this->subscription->trial_ends_at->addDays(2);
        $this->assertTrue($this->subscription->isActive());
        $this->assertEquals(PlanSubscription::STATUS_ACTIVE, $this->subscription->status);

        // Test if subscription is inactive after removing the trial.
        $this->subscription->trial_ends_at = Carbon::now()->subDay();
        $this->subscription->cancel(true);
        $this->assertFalse($this->subscription->isActive());
    }

    /**
     * Can be renewed.
     *
     * @test
     *
     * @return void
     */
    public function it_can_be_renewed()
    {
        Event::fake();

        // Create a subscription with an ended period...
        $subscription = PlanSubscription::factory()->create([
            'plan_id' => Plan::factory()->create([
                'interval' => 'month',
            ])->id,
            'trial_ends_at' => Carbon::now()->subMonth(),
            'ends_at' => Carbon::now()->subMonth(),
        ]);

        $this->assertFalse($subscription->isActive());

        $subscription->renew();

        $this->assertTrue($subscription->isActive());
        $this->assertEquals(Carbon::now()->addMonth()->month, $subscription->ends_at->month);

        Event::assertDispatched(SubscriptionRenewed::class, function ($event) use ($subscription) {
            return (int) $event->subscription->id === (int) $subscription->id;
        });
    }

    /**
     * Can find subscription with an ending trial.
     *
     * @test
     *
     * @return void
     */
    public function it_can_find_subscriptions_with_ending_trial()
    {
        // For "control", these subscription shouldn't be
        // included in the result...
        PlanSubscription::factory(10)->create([
            'trial_ends_at' => Carbon::now()->addDays(10), // End in ten days...
        ]);

        // These are the results that should be returned...
        PlanSubscription::factory(5)->create([
            'trial_ends_at' => Carbon::now()->addDays(3), // Ended a day ago...
        ]);

        $result = PlanSubscription::FindEndingTrial(3)->get();

        $this->assertEquals(5, $result->count());
    }

    /**
     * Can find subscription with an ended trial.
     *
     * @test
     *
     * @return void
     */
    public function it_can_find_subscriptions_with_ended_trial()
    {
        // For "control", these subscription shouldn't be
        // included in the result...
        PlanSubscription::factory(10)->create([
            'trial_ends_at' => Carbon::now()->addDays(2), // End in two days...
        ]);

        // These are the results that should be returned...
        PlanSubscription::factory(5)->create([
            'trial_ends_at' => Carbon::now()->subDay(), // Ended a day ago...
        ]);

        $result = PlanSubscription::FindEndedTrial()->get();

        $this->assertEquals(5, $result->count());
    }

    /**
     * Can find subscription with an ending period.
     *
     * @test
     *
     * @return void
     */
    public function it_can_find_subscriptions_with_ending_period()
    {
        // For "control", these subscription shouldn't be
        // included in the result...
        PlanSubscription::factory(10)->create([
            'ends_at' => Carbon::now()->addDays(10), // End in ten days...
        ]);

        // These are the results that should be returned...
        PlanSubscription::factory(5)->create([
            'ends_at' => Carbon::now()->addDays(3), // Ended a day ago...
        ]);

        $result = PlanSubscription::FindEndingPeriod(3)->get();

        $this->assertEquals(5, $result->count());
    }

    /**
     * Can find subscription with an ended period.
     *
     * @test
     *
     * @return void
     */
    public function it_can_find_subscriptions_with_ended_period()
    {
        // For "control", these subscription shouldn't be
        // included in the result...
        PlanSubscription::factory(10)->create([
            'ends_at' => Carbon::now()->addDays(2), // End in two days...
        ]);

        // These are the results that should be returned...
        PlanSubscription::factory(5)->create([
            'ends_at' => Carbon::now()->subDay(), // Ended a day ago...
        ]);

        $result = PlanSubscription::FindEndedPeriod()->get();

        $this->assertEquals(5, $result->count());
    }

    /**
     * Exclude canceled subscription.
     *
     * @test
     *
     * @return void
     */
    public function it_can_exclude_canceled_subscriptions()
    {
        // Make sure canceled subscriptions are not included...
        PlanSubscription::factory(10)->create([
            'canceled_at' => Carbon::now()->subDays(2),
        ]);

        // Not canceled subscriptions
        PlanSubscription::factory(5)->create([
            'canceled_at' => null,
        ]);

        $result = PlanSubscription::excludeCanceled()->get();

        $this->assertEquals(6, $result->count());
    }

    /**
     * Exclude immediately canceled subscription.
     *
     * @test
     *
     * @return void
     */
    public function it_can_exclude_immediately_canceled_subscriptions()
    {
        // Immediately canceled subscriptions...
        PlanSubscription::factory(2)->create([
            'canceled_at' => Carbon::now(),
            'canceled_immediately' => 1,
        ]);

        // Not immediately canceled subscriptions...
        PlanSubscription::factory(3)->create([
            'canceled_at' => null,
        ]);

        PlanSubscription::factory(2)->create([
            'canceled_at' => null,
            'canceled_immediately' => 0,
        ]);

        $result = PlanSubscription::excludeImmediatelyCanceled()->get();

        $this->assertEquals(6, $result->count());
    }

    /**
     * Can change subscription plan.
     *
     * @test
     *
     * @return void
     */
    public function it_can_change_plan()
    {
        Event::fake([
            SubscriptionPlanChanged::class,
        ]);

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
        $this->assertEquals($expectedPeriodEndDate->format('Y-m-d H:i:s'), $this->subscription->ends_at->format('Y-m-d H:i:s'));

        // This assertion will make sure that the subscription is now using
        // the new plan features...
        $this->assertEquals('Y', $this->subscription->fresh()->ability()->value('listing_title_bold'));

        $subscription = $this->subscription;

        Event::assertDispatched(SubscriptionPlanChanged::class, function ($event) use ($subscription) {
            return (int) $event->subscription->id === (int) $subscription->id;
        });
    }
}
