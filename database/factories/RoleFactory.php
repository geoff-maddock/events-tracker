<?php

use Carbon\Carbon;
use Faker\Generator as Faker;

$factory->define(App\Role::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
        'slug' => $faker->slug,
        'short' => $faker->sentence,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now()
    ];
});
