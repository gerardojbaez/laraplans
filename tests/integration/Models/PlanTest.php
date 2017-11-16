<?php

namespace Gerarodjbaez\Laraplans\Tests\Integration\Models;

use Gerardojbaez\Laraplans\Period;
use Gerardojbaez\Laraplans\Tests\TestCase;
use Gerardojbaez\Laraplans\Models\Plan;
use Gerardojbaez\Laraplans\Models\PlanFeature;

class PlanTest extends TestCase
{
    /**
     * Can create a plan with features attached.
     *
     * @test
     * @return void
     */
    public function it_can_create_a_plan_and_attach_features_to_it()
    {
        $plan = Plan::create([
            'name' => 'Pro',
            'description' => 'Pro plan',
            'price' => 9.99,
            'interval' => 'month',
            'interval_count' => 1,
            'trial_period_days' => 15,
            'sort_order' => 1,
        ]);

        $plan->features()->saveMany([
            new PlanFeature(['code' => 'listings_per_month', 'value' => 50, 'sort_order' => 1]),
            new PlanFeature(['code' => 'pictures_per_listing', 'value' => 10, 'sort_order' => 5]),
            new PlanFeature(['code' => 'listing_duration_days', 'value' => 30, 'sort_order' => 10]),
        ]);

        $plan->fresh();

        $this->assertEquals('Pro', $plan->name);
        $this->assertEquals(3, $plan->features->count());
    }

    /**
     * Can get interval translated name.
     *
     * @test
     * @return void
     */
    public function it_can_get_interval_translated_name()
    {
        $plan = new Plan([
            'interval' => 'month',
        ]);

        $expected = Period::getAllIntervals()['month'];

        $this->assertEquals($expected, $plan->intervalName);
    }

    /**
     * Can get interval description.
     *
     * @test
     * @return void
     */
    public function it_can_get_interval_description()
    {
        $plan = new Plan([
            'interval' => 'month',
            'interval_count' => 1
        ]);

        \App::setLocale('en');
        $this->assertEquals('Monthly', $plan->intervalDescription);

        \App::setLocale('es');
        $this->assertEquals('Mensual', $plan->intervalDescription);
    }

    /**
     * Check if plan is free or not.
     *
     * @test
     * @return void
     */
    public function it_can_check_if_plan_is_free_or_not()
    {
        $free = new Plan([
            'price' => 0.00
        ]);

        $notFree = new Plan([
            'price' => 9.99
        ]);

        $this->assertTrue($free->isFree());
        $this->assertFalse($notFree->isFree());
    }

    /**
     * Check if plan is has trial.
     *
     * @test
     * @return void
     */
    public function it_has_trial()
    {
        $withoutTrial = new Plan([
            'trial_period_days' => 0
        ]);

        $withTrial = new Plan([
            'trial_period_days' => 5
        ]);

        $this->assertTrue($withTrial->hasTrial());
        $this->assertFalse($withoutTrial->hasTrial());
    }
}
