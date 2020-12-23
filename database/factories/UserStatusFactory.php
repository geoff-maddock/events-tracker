<?php

namespace Database\Factories;

use App\Models\UserStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserStatusFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserStatus::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'can_login' => $this->faker->boolean,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ];
    }
}
