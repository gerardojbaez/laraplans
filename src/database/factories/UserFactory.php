<?php

use Gerardojbaez\Laraplans\Tests\Models\User;

$factory->define(User::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->safeEmail,
        'password' => bcrypt(\Illuminate\Support\Str::random(10)),
        'remember_token' => \Illuminate\Support\Str::random(10),
    ];
});
