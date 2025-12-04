<?php

namespace Gerardojbaez\Laraplans\Database\Factories;

use Gerardojbaez\Laraplans\Models\PlanSubscription;
use Gerardojbaez\Laraplans\Models\PlanSubscriptionUsage;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanSubscriptionUsageFactory extends Factory
{
    protected $model = PlanSubscriptionUsage::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'subscription_id' => PlanSubscription::factory(),
            'code' => $this->faker->word(),
            'used' => $this->faker->numberBetween(1, 50),
            'valid_until' => $this->faker->dateTime(),
        ];
    }
}
