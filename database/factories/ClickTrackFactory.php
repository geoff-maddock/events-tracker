<?php

namespace Database\Factories;

use App\Models\ClickTrack;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClickTrack>
 */
class ClickTrackFactory extends Factory
{
    protected $model = ClickTrack::class;

    /**
     * An anonymous click on an event's ticket link, just now.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => fn () => Event::factory()->create()->id,
            'user_id' => null,
            'venue_id' => null,
            'promoter_id' => null,
            'tags' => null,
            'user_agent' => 'Mozilla/5.0 (test)',
            'referrer' => null,
            'ip_address' => '203.0.113.7',
            'country_code' => null,
            'city' => null,
            'clicked_at' => now(),
        ];
    }
}
