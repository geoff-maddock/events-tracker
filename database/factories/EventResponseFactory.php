<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventResponse;
use App\Models\ResponseType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventResponseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EventResponse::class;

    /**
     * Define the model's default state.
     *
     * Defaults to Attending, which is the only response type the application
     * actually writes.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'event_id' => fn () => Event::factory()->create()->id,
            'user_id' => fn () => User::factory()->create()->id,
            'response_type_id' => ResponseType::ATTENDING,
        ];
    }

    /**
     * Interested, rather than attending.
     */
    public function interested(): static
    {
        return $this->state(fn () => ['response_type_id' => ResponseType::INTERESTED]);
    }
}
