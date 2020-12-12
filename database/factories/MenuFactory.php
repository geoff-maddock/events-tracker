<?php

use App\Models\Menu;
use App\Models\Visibility;
use Carbon\Carbon;
use Faker\Generator as Faker;

$factory->define(App\Menu::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
        'slug' => $faker->slug,
        'body' => $faker->sentence,
        'menu_parent_id' => function () {
            return Menu::all()->random()->id;
        },
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
        'visibility_id' => function () {
            return Visibility::all()->random()->id;
        },
    ];
});
