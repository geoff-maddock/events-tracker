<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    /**
     * A comment on an event, by a new user.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'message' => $this->faker->sentence(),
            'commentable_type' => 'event',
            'commentable_id' => fn () => Event::factory()->create()->id,
            'created_by' => fn () => User::factory()->create()->id,
        ];
    }

    /**
     * On another model: its morph alias is the stored type.
     */
    public function on(Model $commentable): static
    {
        return $this->state(fn () => [
            'commentable_type' => $commentable->getMorphClass(),
            'commentable_id' => $commentable->getKey(),
        ]);
    }
}
