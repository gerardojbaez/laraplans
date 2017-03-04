<?php

use Gerardojbaez\LaraPlans\Models\Plan;
use Gerardojbaez\LaraPlans\Tests\Models\User;
use Gerardojbaez\LaraPlans\Models\PlanSubscription;

$factory->define(PlanSubscription::class, function (Faker\Generator $faker) {
    return [
        'user_id' => factory(User::class)->create()->id,
        'plan_id' => factory(Plan::class)->create()->id,
        'name' => $faker->word
    ];
});
