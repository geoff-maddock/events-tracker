<?php

use App\Models\User;
use Carbon\Carbon;
use Faker\Generator as Faker;

$factory->define(App\Photo::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
        'thumbnail' => $faker->file,
        'path' => $faker->file,
        'caption' => $faker->sentence,
        'is_public' => $faker->boolean,
        'is_primary' => $faker->boolean,
        'is_approved' => $faker->boolean,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
        'created_by' => function () {
            return User::all()->random()->id;
        },
        'updated_by' => function () {
            return User::all()->random()->id;
        }
    ];
});
