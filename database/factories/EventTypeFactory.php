<?php

use App\Models\EventType;
use Carbon\Carbon;
use Faker\Generator as Faker;

$factory->define(EventType::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now()
    ];
});
