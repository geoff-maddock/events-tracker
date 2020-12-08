<?php

use Carbon\Carbon;
use Faker\Generator as Faker;

$factory->define(App\Event::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'slug' => $faker->slug(),
        'short' => $faker->name,
        'short' => $faker->paragraph,
        'description' => $faker->paragraph,
        'visibility_id' => random_int(1, 3),
        'event_status_id' => random_int(1, 3),
        'event_type_id' => random_int(1, 5),
        'is_benefit' => $faker->boolean(),
        'promoter_id' => factory(App\Entity::class)->states('promoter')->make(),
        'venue_id' => factory(App\Entity::class)->states('venue')->make(),
        'attending' => random_int(0, 100),
        'like' => random_int(0, 10),
        'presale_price' => random_int(0, 50),
        'door_price' => random_int(0, 50),
        'soundcheck_at' => now(),
        'door_at' => now(),
        'start_at' => now(),
        'end_at' => Carbon::now()->addHour(),
        'min_age' => $faker->randomElement([null, 18, 21]),
        'series_id' => null,
        'primary_link' => $faker->url,
        'ticket_link' => $faker->url,
        'created_by' => factory(App\User::class)->create(),
        'updated_by' => factory(App\User::class)->create(),
        'created_at' => now(),
        'updated_at' => now(),
        'cancelled_at' => null
    ];
});
