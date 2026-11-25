<?php

namespace Database\Factories;

use App\Models\Entity;
use App\Models\Follow;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Follow>
 */
class FollowFactory extends Factory
{
    protected $model = Follow::class;

    /**
     * A new user's follow of a new entity.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => fn () => User::factory()->create()->id,
            'object_type' => 'entity',
            'object_id' => fn () => Entity::factory()->create()->id,
        ];
    }

    /**
     * Of another model: its morph alias is the stored type.
     */
    public function of(Model $object): static
    {
        return $this->state(fn () => [
            'object_type' => $object->getMorphClass(),
            'object_id' => $object->getKey(),
        ]);
    }
}
