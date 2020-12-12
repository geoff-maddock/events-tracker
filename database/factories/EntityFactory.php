<?php

use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\EntityType;
use Faker\Generator as Faker;

$factory->define(Entity::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'slug' => $faker->name,
        'short' => $faker->paragraph,
        'description' => $faker->paragraph,
        'entity_type_id' => function () {
            return EntityType::all()->random()->id;
        },
        'entity_status_id' => function () {
            return EntityStatus::all()->random()->id;
        },
        'facebook_username' => $faker->name,
        'twitter_username' => $faker->name,
        'created_by' => 1
    ];
});

$factory->state(Entity::class, 'venue', [
    'entity_type_id' => EntityType::SPACE
]);

$factory->state(Entity::class, 'promoter', [
    'entity_type_id' => EntityType::INDIVIDUAL
]);
