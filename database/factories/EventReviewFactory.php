<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventReview;
use App\Models\ReviewType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventReview>
 */
class EventReviewFactory extends Factory
{
    protected $model = EventReview::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => fn () => Event::factory()->create()->id,
            'user_id' => fn () => User::factory()->create()->id,
            'review_type_id' => fn () => ReviewType::query()->value('id') ?? ReviewType::factory()->create()->id,
            'attended' => 1,
            'confirmed' => 0,
            'expectation' => 3,
            'rating' => 4,
            'review' => $this->faker->sentence(),
        ];
    }
}
