<?php

use Carbon\Carbon;
use Faker\Generator as Faker;

$factory->define(App\Location::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
        'slug' => $faker->slug,
        'attn' => $faker->optional->name,
        'address_one' => $faker->streetAddress,
        'address_two' => $faker->optional->streetAddress,
        'city' => $faker->city,
        'state' => $faker->state,
        'postcode' => $faker->postcode,
        'location_type_id' => function () {
            return App\LocationType::all()->random()->id;
        },
        'entity_id' => function () {
            return App\Entity::all()->random()->id;
        },
        'capacity' => $faker->random_int(0, 100),
        'map_url' => $faker->optional->url,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
        'created_by' => function () {
            return App\User::all()->random()->id;
        },
        'updated_by' => function () {
            return App\User::all()->random()->id;
        },
        'visibility_id' => function () {
            return App\Visibility::all()->random()->id;
        },
    ];
});