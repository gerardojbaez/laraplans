<?php

namespace Gerardojbaez\Laraplans\Database\Factories;

use Gerardojbaez\Laraplans\Models\Plan;
use Gerardojbaez\Laraplans\Models\PlanSubscription;
use Gerardojbaez\Laraplans\Tests\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanSubscriptionFactory extends Factory
{
    protected $model = PlanSubscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subscribable_id' => User::factory()->create()->id,
            'subscribable_type' => User::class,
            'plan_id' => Plan::factory()->create()->id,
            'name' => fake()->word,
        ];
    }
}
