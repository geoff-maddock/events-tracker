<?php

namespace Database\Factories;

use App\Models\Entity;
use App\Models\EventType;
use App\Models\OccurrenceDay;
use App\Models\OccurrenceType;
use App\Models\OccurrenceWeek;
use App\Models\Series;
use App\Models\Visibility;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeriesFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Series::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $user = User::factory()->create();

        return [
            'name' => $this->faker->name,
            'slug' => $this->faker->name,
            'short' => $this->faker->paragraph,
            'description' => $this->faker->paragraph,
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
            'hold_date' => $this->faker->boolean,
            'is_benefit' => $this->faker->boolean,
            'promoter_id' => Entity::factory()->promoter()->make(),
            'venue_id' => Entity::factory()->venue()->make(),
            'attending' => random_int(0, 100),
            'like' => random_int(0, 10),
            'presale_price' => random_int(0, 50),
            'door_price' => random_int(0, 50),
            'soundcheck_at' => now(),
            'door_at' => now(),
            'start_at' => now(),
            'end_at' => Carbon::now()->addHour(),
            'min_age' => $this->faker->randomElement([null, 18, 21]),
            'primary_link' => $this->faker->url,
            'ticket_link' => $this->faker->url,
            'created_by' => User::factory()->create(),
            'updated_by' => User::factory()->create(),
            'created_at' => now(),
            'updated_at' => now(),
            'cancelled_at' => null,
            'founded_at' => now()
        ];
    }
}
