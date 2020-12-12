<?php

use App\Models\ReviewType;
use Carbon\Carbon;
use Faker\Generator as Faker;

$factory->define(ReviewType::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now()
    ];
});
