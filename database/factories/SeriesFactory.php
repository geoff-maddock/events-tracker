<?php

namespace Database\Factories;

use App\Models\EventType;
use App\Models\OccurrenceDay;
use App\Models\OccurrenceType;
use App\Models\OccurrenceWeek;
use App\Models\Series;
use App\Models\Visibility;
use App\Models\User;
use Carbon\Carbon;
use Faker\Generator as Faker;

$factory->define(Series::class, function (Faker $faker) {
    $user = factory(User::class)->create();

    return [
        'name' => $faker->name,
        'slug' => $faker->name,
        'short' => $faker->paragraph,
        'description' => $faker->paragraph,
        'event_type_id' => random_int(1, 5),
        'visibility_id' => function () {
            return Visibility::all()->random()->id;
        },
        'event_type_id' => function () {
            return EventType::all()->random()->id;
        },
        'occurrence_type_id' => function () {
            return OccurrenceType::all()->random()->id;
        },
        'occurrence_week_id' => function () {
            return OccurrenceWeek::all()->random()->id;
        },
        'occurrence_day_id' => function () {
            return OccurrenceDay::all()->random()->id;
        },
        'hold_date' => $faker->boolean,
        'is_benefit' => $faker->boolean,
        'promoter_id' => factory(Entity::class)->states('promoter')->make(),
        'venue_id' => factory(Entity::class)->states('venue')->make(),
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
        'created_by' => factory(User::class)->create(),
        'updated_by' => factory(User::class)->create(),
        'created_at' => now(),
        'updated_at' => now(),
        'cancelled_at' => null,
        'founded_at' => now()
    ];
});
