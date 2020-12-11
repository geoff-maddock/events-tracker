<?php

use Carbon\Carbon;
use Faker\Generator as Faker;

$factory->define(App\Menu::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
        'slug' => $faker->slug,
        'body' => $faker->sentence,
        'menu_parent_id' => function () {
            return App\Menu::all()->random()->id;
        },
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
        'visibility_id' => function () {
            return App\Visibility::all()->random()->id;
        },
    ];
});
