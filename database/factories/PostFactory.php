<?php

use App\Models\Post;
use App\Models\Thread;
use App\Models\Visibility;
use App\Models\User;
use Faker\Generator as Faker;

$factory->define(Post::class, function (Faker $faker) {
    return [
        'thread_id' => function () {
            return factory(Thread::class)->create()->id;
        },
        'created_by' => function () {
            return User::all()->last()->id;
        },
        'name' => $faker->sentence,
        'slug' => $faker->sentence,
        'description' => $faker->paragraph,
        'body' => $faker->paragraph,
        'likes' => $faker->numberBetween(0, 10),
        'views' => $faker->numberBetween(0, 10),
        'is_active' => $faker->boolean,
        'allow_html' => $faker->boolean,
        'visibility_id' => function () {
            return Visibility::all()->last()->id;
        },
    ];
});
