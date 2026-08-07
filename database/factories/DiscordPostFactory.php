<?php

namespace Database\Factories;

use App\Models\DiscordPost;
use App\Models\DiscordTarget;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscordPost>
 */
class DiscordPostFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DiscordPost::class;

    /**
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'discord_target_id' => DiscordTarget::factory(),
            'event_id' => Event::factory(),
            'reason' => DiscordPost::REASON_CREATE,
            'reason_key' => '',
            'status' => DiscordPost::STATUS_SENT,
            'message_id' => (string) $this->faker->unique()->numerify('##################'),
            'channel_id' => (string) $this->faker->numerify('##################'),
            'attempts' => 1,
            'posted_at' => now(),
        ];
    }

    public function failed(string $error = 'Discord rejected the payload'): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => DiscordPost::STATUS_FAILED,
            'message_id' => null,
            'channel_id' => null,
            'error' => $error,
            'posted_at' => null,
        ]);
    }

    public function reminder(int $offsetDays): static
    {
        return $this->state(fn (array $attributes): array => [
            'reason' => DiscordPost::REASON_REMINDER,
            'reason_key' => (string) $offsetDays,
        ]);
    }

    /**
     * A digest row is about a week, not an event, so it carries event_id 0.
     */
    public function digest(string $weekKey): static
    {
        return $this->state(fn (array $attributes): array => [
            'event_id' => 0,
            'reason' => DiscordPost::REASON_DIGEST,
            'reason_key' => $weekKey,
        ]);
    }
}
