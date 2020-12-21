<?php

namespace Database\Factories;

use App\Models\Forum;
use App\Models\Thread;
use App\Models\ThreadCategory;
use App\Models\Visibility;
use App\Models\User;
use Faker\Generator as Faker;

$factory->define(Thread::class, function (Faker $faker) {
    $user = factory(User::class)->create();
    $forum = factory(Forum::class)->create();
    $threadCategory = factory(ThreadCategory::class, 3)->create();

    return [
        'forum_id' => function () {
            return Forum::all()->random()->id;
        },
        'thread_category_id' => function () {
            return ThreadCategory::all()->random()->id;
        },
        'name' => $faker->sentence,
        'slug' => $faker->sentence,
        'description' => $faker->paragraph,
        'body' => $faker->paragraph,
        'allow_html' => $faker->boolean,
        'visibility_id' => function () {
            return Visibility::all()->random()->id;
        },
        'recipient_id' => null,
        'sort_order' => $faker->boolean,
        'is_edittable' => $faker->boolean,
        'likes' => random_int(0, 20),
        'views' => random_int(0, 100),
        'is_active' => $faker->boolean,
        'created_by' => function () {
            return User::all()->random()->id;
        },
        'updated_by' => function () {
            return User::all()->random()->id;
        },
        'created_at' => $faker->dateTimeBetween($startDate = '-1 years', $endDate = 'now', $timezone = null),
        'updated_at' => $faker->dateTimeBetween($startDate = '-1 months', $endDate = 'now', $timezone = null),
        'locked_by' => null,
        'locked_at' => null
    ];
});
