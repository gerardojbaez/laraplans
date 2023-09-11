<?php
namespace Gerardojbaez\Laraplans\Database\Factories;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;
use Gerardojbaez\Laraplans\Models\Plan;
use Gerardojbaez\Laraplans\Tests\Models\User;
use Gerardojbaez\Laraplans\Models\PlanSubscription;

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
            'name' => fake()->word
        ];
    }
}
