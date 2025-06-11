<?php

namespace Database\Factories;

use App\Models\Visibility;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class VisibilityFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Visibility::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ];
    }
}
