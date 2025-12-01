<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Entity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Event::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'slug' => $this->faker->slug(),
            'short' => $this->faker->name,
            'description' => $this->faker->paragraph,
            'visibility_id' => random_int(1, 3),
            'event_status_id' => random_int(1, 3),
            'event_type_id' => random_int(1, 5),
            'is_benefit' => $this->faker->boolean(),
            'do_not_repost' => false,
            'promoter_id' => Entity::factory()->promoter()->create(),
            'venue_id' => Entity::factory()->venue()->create(),
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
            'created_by' => function () {
                return User::factory()->create();
            },
            'created_at' => now(),
            'updated_at' => now(),
            'updated_by' => function () {
                return User::factory()->create();
            },
        ];
    }
}
