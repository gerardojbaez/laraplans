<?php

namespace Gerardojbaez\Laraplans\Database\Factories;


use Gerardojbaez\Laraplans\Models\PlanSubscription;
use Gerardojbaez\Laraplans\Models\PlanSubscriptionUsage;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanSubscriptionUsageFactory extends Factory
{

    protected $model = PlanSubscriptionUsage::class;
    /**
     * @return mixed[]
     */
    public function definition()
    {
        return [
            'name' => fake()->word,
            'subscription_id' => PlanSubscription::factory()->create()->id,
            'code' => fake()->word,
            'used' => rand(1, 50),
            'valid_until' => fake()->dateTime()
        ];    }
}
