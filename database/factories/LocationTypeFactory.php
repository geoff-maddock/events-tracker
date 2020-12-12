<?php

use App\Models\LocationType;
use Carbon\Carbon;
use Faker\Generator as Faker;

$factory->define(LocationType::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now()
    ];
});
