<?php

use App\Models\Tag;
use App\Models\TagType;
use Carbon\Carbon;
use Faker\Generator as Faker;

$factory->define(Tag::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
        'tag_type_id' => function () {
            return TagType::all()->random()->id;
        },
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now()
    ];
});
