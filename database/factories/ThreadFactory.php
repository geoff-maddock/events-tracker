<?php

use Faker\Generator as Faker;

$factory->define(App\Thread::class, function (Faker $faker) {
    $user = factory(App\User::class)->create();
    $forum = factory(App\Forum::class)->create();

    return [
        'forum_id' => 1,
        'created_by' => 1,
        'name' => $faker->sentence,
        'slug' => $faker->sentence,
        'body' => $faker->paragraph,
        'description' => $faker->paragraph,
        'visibility_id' => 3
    ];
});
