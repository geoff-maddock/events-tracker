<?php

use App\Models\User;
use Faker\Generator as Faker;

$factory->define(App\Profile::class, function (Faker $faker) {
    $user = factory(User::class)->create();

    return [
        'user_id' => function () {
            return User::all()->random()->id;
        },
        'first_name' => $faker->name,
        'last_name' => $faker->name,
        'default_theme' => 'light-theme',
        'bio' => $faker->paragraph,
        'location' => $faker->optional->city
    ];
});
