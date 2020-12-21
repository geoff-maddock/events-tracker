<?php

namespace Database\Factories;

use App\Models\ThreadCategory;
use Faker\Generator as Faker;

$factory->define(ThreadCategory::class, function (Faker $faker) {
    return [
        'forum_id' => 1,
        'name' => $faker->word
    ];
});
