<?php

namespace Gerardojbaez\Laraplans\Database\Factories;

use Gerardojbaez\Laraplans\Tests\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'password' => bcrypt(Str::random(10)),
            'remember_token' => Str::random(10),
        ];
    }
}
