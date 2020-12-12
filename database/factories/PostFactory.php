<?php

use App\Models\Visibility;
use App\Models\User;
use Faker\Generator as Faker;

$factory->define(App\Post::class, function (Faker $faker) {
    return [
        'thread_id' => function () {
            return factory('App\Model\Thread')->create()->id;
        },
        'created_by' => function () {
            return User::all()->last()->id;
        },
        'name' => $faker->sentence,
        'slug' => $faker->sentence,
        'description' => $faker->paragraph,
        'body' => $faker->paragraph,
        'likes' => $faker->random_int(0, 10),
        'views' => $faker->random_int(0, 10),
        'is_active' => $faker->boolean,
        'allow_html' => $faker->boolean,
        'visibility_id' => function () {
            return Visibility::all()->last()->id;
        },
    ];
});
