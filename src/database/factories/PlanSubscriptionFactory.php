<?php

use Czechbox\LaravelPlans\Models\Plan;
use Czechbox\LaravelPlans\Tests\Models\User;
use Czechbox\LaravelPlans\Models\PlanSubscription;

$factory->define(PlanSubscription::class, function (Faker\Generator $faker) {
    return [
        'subscribable_id' => factory(User::class)->create()->id,
        'subscribable_type' => User::class,
        'plan_id' => factory(Plan::class)->create()->id,
        'name' => $faker->word
    ];
});
