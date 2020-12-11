<?php

use Carbon\Carbon;
use Faker\Generator as Faker;

$factory->define(App\Tag::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
        'tag_type_id' => function () {
            return App\TagType::all()->random()->id;
        },
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now()
    ];
});
