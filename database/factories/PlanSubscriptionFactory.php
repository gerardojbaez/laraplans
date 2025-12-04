<?php

namespace Gerardojbaez\Laraplans\Database\Factories;

use App\Models\User;
use Gerardojbaez\Laraplans\Models\Plan;
use Gerardojbaez\Laraplans\Models\PlanSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanSubscriptionFactory extends Factory
{
    protected $model = PlanSubscription::class;

    public function definition(): array
    {
        return [
            'plan_id' => Plan::factory(),
            'name' => $this->faker->word(),
        ];
    }

    public function configure()
    {
        return $this->afterMaking(function (PlanSubscription $subscription) {
            if (empty($subscription->subscribable_type) || empty($subscription->subscribable_id)) {
                $user = User::factory()->create();
                $subscription->subscribable()->associate($user);
            }
        })->afterCreating(function (PlanSubscription $subscription) {
            if (empty($subscription->subscribable_type) || empty($subscription->subscribable_id)) {
                $user = User::factory()->create();
                $subscription->subscribable()->associate($user);
                $subscription->save();
            }
        });
    }
}
