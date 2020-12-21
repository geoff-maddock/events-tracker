<?php

namespace Database\Factories; */

use App\Models\User;
use App\Models\UserStatus;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

$factory->define(User::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'email_verified_at' => now(),
        'password' => Hash::make('password'),
        'remember_token' => Str::random(10),
        'user_status_id' => function () {
            return UserStatus::all()->random()->id;
        },
        'created_at' => now(),
        'updated_at' => now()
    ];
});
