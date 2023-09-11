<?php
namespace Gerardojbaez\Laraplans\Database\Factories;

use Gerardojbaez\Laraplans\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Facade;

class PlanFactory extends Factory
{

    protected $model = Plan::class;
    /**
     * @return mixed[]
     */
    public function definition()
    {
        return [
            'name' => fake()->word,
            'description' => fake()->sentence,
            'price' => rand(0, 9),
            'interval' => fake()->randomElement(['month', 'year'])
        ];
    }
}
