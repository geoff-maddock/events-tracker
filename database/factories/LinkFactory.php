<?php

use App\Models\ContentType;
use App\Models\Link;
use Carbon\Carbon;
use Faker\Generator as Faker;

$factory->define(Link::class, function (Faker $faker) {
    return [
        'url' => $faker->url,
        'text' => $faker->url,
        'image' => $faker->optional->paragraph,
        'api' => $faker->optional->url,
        'title' => $faker->sentence(random_int(1, 6)),
        'confirm' => $faker->boolean,
        'is_primary' => $faker->boolean,
        'is_active' => $faker->boolean,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now()
    ];
});
