<?php

namespace Database\Factories;

use App\Models\Thread;
use App\Models\Like;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Like>
 */
class LikeFactory extends Factory
{
    protected $model = Like::class;

    /**
     * A new user's like of a new thread: likes are kept for threads and posts.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => fn () => User::factory()->create()->id,
            'object_type' => 'thread',
            'object_id' => fn () => Thread::factory()->create()->id,
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
