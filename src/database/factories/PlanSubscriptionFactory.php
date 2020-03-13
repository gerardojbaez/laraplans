<?php

use Czechbox\Laraplans\Models\Plan;
use Czechbox\Laraplans\Tests\Models\User;
use Czechbox\Laraplans\Models\PlanSubscription;

$factory->define(PlanSubscription::class, function (Faker\Generator $faker) {
    return [
        'subscribable_id' => factory(User::class)->create()->id,
        'subscribable_type' => User::class,
        'plan_id' => factory(Plan::class)->create()->id,
        'name' => $faker->word
    ];
});
