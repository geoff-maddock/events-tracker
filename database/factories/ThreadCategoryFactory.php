<?php

use Faker\Generator as Faker;

$factory->define(App\ThreadCategory::class, function (Faker $faker) {
    return [
        'forum_id' => 1,
        'name' => $faker->word
    ];
});
