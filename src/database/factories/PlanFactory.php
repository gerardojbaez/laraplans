<?php

use Gerardojbaez\Laraplans\Models\Plan;

$factory->define(Plan::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->word,
        'description' => $faker->sentence,
        'price' => rand(0, 9),
        'interval' => $faker->randomElement(['month','year'])
    ];
});
