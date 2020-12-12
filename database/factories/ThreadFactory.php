<?php

use Faker\Generator as Faker;

$factory->define(App\Thread::class, function (Faker $faker) {
    $user = factory(App\User::class)->create();
    $forum = factory(App\Forum::class)->create();
    $threadCategory = factory(App\ThreadCategory::class, 3)->create();

    return [
        'forum_id' => function () {
            return App\Forum::all()->random()->id;
        },
        'thread_category_id' => function () {
            return App\ThreadCategory::all()->random()->id;
        },
        'name' => $faker->sentence,
        'slug' => $faker->sentence,
        'description' => $faker->paragraph,
        'body' => $faker->paragraph,
        'allow_html' => $faker->boolean,
        'visibility_id' => function () {
            return App\Visibility::all()->random()->id;
        },
        'recipient_id' => null,
        'sort_order' => $faker->boolean,
        'is_edittable' => $faker->boolean,
        'likes' => random_int(0, 20),
        'views' => random_int(0, 100),
        'is_active' => $faker->boolean,
        'created_by' => function () {
            return App\User::all()->random()->id;
        },
        'updated_by' => function () {
            return App\User::all()->random()->id;
        },
        'created_at' => $faker->dateTimeBetween($startDate = '-1 years', $endDate = 'now', $timezone = null),
        'updated_at' => $faker->dateTimeBetween($startDate = '-1 months', $endDate = 'now', $timezone = null),
        'locked_by' => null,
        'locked_at' => null
    ];
});
