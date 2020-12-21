<?php

namespace Database\Factories;

use App\Models\ResponseType;
use Carbon\Carbon;
use Faker\Generator as Faker;

$factory->define(ResponseType::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
        'description' => $faker->sentence,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now()
    ];
});
