<?php

use Carbon\Carbon;
use Faker\Generator as Faker;

$factory->define(App\TagType::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now()
    ];
});