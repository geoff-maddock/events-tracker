<?php

use Faker\Generator as Faker;

$factory->define(Forum::class, function (Faker $faker) {
    $user = factory(User::class)->create();

    return [
        'name' => $faker->sentence,
        'slug' => $faker->sentence,
        'description' => $faker->sentence,
        'visibility_id' => 3,
        'sort_order' => 0,
        'is_active' => 1,
        'created_by' => $user->id
    ];
});
