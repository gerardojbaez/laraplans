<?php

namespace Gerardojbaez\Laraplans\Database\Factories;

use Gerardojbaez\Laraplans\Models\Plan;
use Gerardojbaez\Laraplans\Models\PlanFeature;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFeatureFactory extends Factory
{
    protected $model = PlanFeature::class;

    public function definition(): array
    {
        return [
            'plan_id' => Plan::factory(),
            'code' => $this->faker->word(),
            'value' => $this->faker->randomElement(['10', '20', '30', '50', 'Y', 'N', 'UNLIMITED']),
        ];
    }
}
