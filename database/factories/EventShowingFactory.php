<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventShowing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * An additional date of an event. The app writes these through
 * Event::syncShowings(); a test that only needs a row can use this.
 *
 * @extends Factory<EventShowing>
 */
class EventShowingFactory extends Factory
{
    protected $model = EventShowing::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => fn () => Event::factory()->create()->id,
            // the day after the event starts: a showing is always a later date
            'start_at' => fn (array $attributes) => Event::query()->findOrFail($attributes['event_id'])->start_at->copy()->addDay(),
            'end_at' => null,
            'door_at' => null,
        ];
    }

    public function configure(): static
    {
        // what syncShowings() does for the app: the event's last_start_at reaches
        // its last showing, which the date-window lookups read (#2391)
        return $this->afterCreating(function (EventShowing $showing) {
            Event::query()->whereKey($showing->event_id)
                ->where('last_start_at', '<', $showing->start_at)
                ->update(['last_start_at' => $showing->start_at]);
        });
    }
}
