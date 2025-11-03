<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Activity::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'object_table' => $this->faker->randomElement(['Event', 'Entity', 'Thread', 'Post', 'Series']),
            'object_id' => $this->faker->numberBetween(1, 100),
            'object_name' => $this->faker->sentence(3),
            'child_object_table' => null,
            'child_object_id' => null,
            'child_object_name' => null,
            'action_id' => random_int(1, 5),
            'message' => $this->faker->sentence,
            'changes' => null,
            'ip_address' => $this->faker->ipv4,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
