<?php

namespace Database\Factories;

use App\Models\DiscordTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscordTarget>
 */
class DiscordTargetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DiscordTarget::class;

    /**
     * Define the model's default state.
     *
     * A default target matches nothing — match_all is off and no criteria are
     * attached — which mirrors the fail-safe production behaviour. Tests that
     * want it to receive something use ->matchAll() or attach criteria.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => $this->faker->unique()->slug(2),
            'webhook_url' => 'https://discord.com/api/webhooks/'
                .$this->faker->unique()->numerify('##################')
                .'/'.$this->faker->regexify('[A-Za-z0-9_-]{68}'),
            'is_enabled' => true,
            'match_all' => false,
            'requires_photo' => true,
            'post_on_create' => true,
            'post_reminders' => false,
            'post_digest' => false,
            'allow_manual' => true,
            'reminder_offsets' => null,
            'digest_day' => 4,
            'digest_hour' => 10,
            'digest_window_days' => 7,
        ];
    }

    /**
     * Receives every eligible event, with no criteria needed.
     */
    public function matchAll(): static
    {
        return $this->state(fn (array $attributes): array => ['match_all' => true]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes): array => ['is_enabled' => false]);
    }

    /**
     * @param  array<int, int>|null  $offsets
     */
    public function reminders(?array $offsets = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'post_reminders' => true,
            'reminder_offsets' => $offsets,
        ]);
    }

    public function digest(int $day = 4, int $hour = 10): static
    {
        return $this->state(fn (array $attributes): array => [
            'post_digest' => true,
            'digest_day' => $day,
            'digest_hour' => $hour,
        ]);
    }

    /**
     * Accepts events that have no flyer yet.
     */
    public function withoutPhotoRequirement(): static
    {
        return $this->state(fn (array $attributes): array => ['requires_photo' => false]);
    }
}
