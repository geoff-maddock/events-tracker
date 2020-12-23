<?php

namespace Database\Factories;

use App\Models\Group;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Group::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'label' => $this->faker->name,
            'level' => $this->faker->randomElement([0, 1, 2, 10, 100, 999]),
            'description' => $this->faker->sentence,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ];
    }
}
