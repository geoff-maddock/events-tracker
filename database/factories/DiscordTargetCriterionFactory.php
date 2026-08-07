<?php

namespace Database\Factories;

use App\Models\DiscordTarget;
use App\Models\DiscordTargetCriterion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscordTargetCriterion>
 */
class DiscordTargetCriterionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DiscordTargetCriterion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'discord_target_id' => DiscordTarget::factory(),
            'criteria_type' => DiscordTargetCriterion::TYPE_TAG,
            'criteria_id' => 1,
            'mode' => DiscordTargetCriterion::MODE_INCLUDE,
        ];
    }

    public function ofType(string $type, int $id): static
    {
        return $this->state(fn (array $attributes): array => [
            'criteria_type' => $type,
            'criteria_id' => $id,
        ]);
    }

    public function exclude(): static
    {
        return $this->state(fn (array $attributes): array => [
            'mode' => DiscordTargetCriterion::MODE_EXCLUDE,
        ]);
    }
}
