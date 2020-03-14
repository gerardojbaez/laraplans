<?php

use Czechbox\LaravelPlans\Tests\Models\User;

$factory->define(User::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->safeEmail,
        'password' => bcrypt(Str::random(10)),
        'remember_token' => Str::random(10),
    ];
});
