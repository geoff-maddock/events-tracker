<?php

use App\EntityType;
use Faker\Generator as Faker;

$factory->define(App\Entity::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'slug' => $faker->name,
        'short' => $faker->paragraph,
        'description' => $faker->paragraph,
        'entity_type_id' => function () {
            return App\EntityType::all()->random()->id;
        },
        'entity_status_id' => function () {
            return App\EntityStatus::all()->random()->id;
        },
        'facebook_username' => $faker->name,
        'twitter_username' => $faker->name,
        'created_by' => 1
    ];
});

$factory->state(App\Entity::class, 'venue', [
    'entity_type_id' => EntityType::SPACE
]);

$factory->state(App\Entity::class, 'promoter', [
    'entity_type_id' => EntityType::INDIVIDUAL
]);
