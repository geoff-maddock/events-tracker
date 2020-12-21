<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\User;
use Carbon\Carbon;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

$factory->define(Group::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'label' => $faker->name,
        'level' => $faker->randomElement([0, 1, 2, 10, 100, 999]),
        'description' => $faker->sentence,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now()
    ];
});
