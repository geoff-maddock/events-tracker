<?php

namespace Database\Factories;

use App\Models\Forum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ForumFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Forum::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $user = User::factory()->create();

        return [
            'name' => $this->faker->sentence,
            'slug' => $this->faker->sentence,
            'description' => $this->faker->sentence,
            'visibility_id' => 3,
            'sort_order' => 0,
            'is_active' => 1,
            'created_by' => $user->id
        ];
    }
}
