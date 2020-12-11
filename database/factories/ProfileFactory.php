<?php

use Faker\Generator as Faker;

$factory->define(App\Profile::class, function (Faker $faker) {
    $user = factory('App\User')->create();

    return [
        'user_id' => function () {
            return App\User::all()->random()->id;
        },
        'first_name' => $faker->name,
        'last_name' => $faker->name,
        'default_theme' => 'light-theme',
        'bio' => $faker->paragraph,
        'location' => $faker->optional->city
    ];
});
