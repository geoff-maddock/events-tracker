<?php

namespace Database\Factories;

use App\Models\Entity;
use App\Models\EntityClaim;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EntityClaim>
 */
class EntityClaimFactory extends Factory
{
    protected $model = EntityClaim::class;

    /**
     * A pending claim.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'entity_id' => fn () => Entity::factory()->create()->id,
            'user_id' => fn () => User::factory()->create()->id,
            'status' => EntityClaim::STATUS_PENDING,
            'message' => $this->faker->sentence(),
            'evidence_url' => null,
        ];
    }

    public function approved(): static
    {
        return $this->reviewed(EntityClaim::STATUS_APPROVED);
    }

    public function denied(): static
    {
        return $this->reviewed(EntityClaim::STATUS_DENIED);
    }

    private function reviewed(string $status): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'reviewed_by' => fn () => User::factory()->create()->id,
            'reviewed_at' => now(),
        ]);
    }
}
