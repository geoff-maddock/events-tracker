<?php

namespace Database\Factories;

use App\Models\Forum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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

        // always valid for ForumRequest: name min 3 chars, slug lowercase/digits/hyphens
        $name = ucfirst($this->faker->words(2, true)).' Forum';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.$this->faker->unique()->numberBetween(1, 1000000),
            'description' => $this->faker->sentence,
            'visibility_id' => 3,
            'sort_order' => 0,
            'is_active' => 1,
            'created_by' => $user->id,
        ];
    }
}
