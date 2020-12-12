<?php

use App\Models\UserStatus;
use Carbon\Carbon;
use Faker\Generator as Faker;

$factory->define(UserStatus::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
        'can_login' => $faker->boolean,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now()
    ];
});
