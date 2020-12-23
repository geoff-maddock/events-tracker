<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\Thread;
use App\Models\Visibility;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'thread_id' => function () {
                return Thread::factory()->create()->id;
            },
            'created_by' => function () {
                return User::all()->last()->id;
            },
            'name' => $this->faker->sentence,
            'slug' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'body' => $this->faker->paragraph,
            'likes' => $this->faker->numberBetween(0, 10),
            'views' => $this->faker->numberBetween(0, 10),
            'is_active' => $this->faker->boolean,
            'allow_html' => $this->faker->boolean,
            'visibility_id' => function () {
                return Visibility::all()->last()->id;
            },
        ];
    }
}
