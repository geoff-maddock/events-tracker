<?php

namespace Database\Factories;

use App\Models\ThreadCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ThreadCategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ThreadCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'forum_id' => 1,
            'name' => $this->faker->word
        ];
    }
}
