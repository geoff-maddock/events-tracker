<?php

namespace Database\Factories;

use App\Models\Entity;
use App\Models\Location;
use App\Models\LocationType;
use App\Models\Visibility;
use App\Models\User;
use Carbon\Carbon;
use Faker\Generator as Faker;

$factory->define(Location::class, function (Faker $faker) {
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
            return LocationType::all()->random()->id;
        },
        'entity_id' => function () {
            return Entity::all()->random()->id;
        },
        'capacity' => $faker->random_int(0, 100),
        'map_url' => $faker->optional->url,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
        'created_by' => function () {
            return User::all()->random()->id;
        },
        'updated_by' => function () {
            return User::all()->random()->id;
        },
        'visibility_id' => function () {
            return Visibility::all()->random()->id;
        },
    ];
});
