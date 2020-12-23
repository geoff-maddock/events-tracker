<?php

namespace Database\Factories;

use App\Models\Link;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class LinkFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Link::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'url' => $this->faker->url,
            'text' => $this->faker->url,
            'image' => $this->faker->optional->paragraph,
            'api' => $this->faker->optional->url,
            'title' => $this->faker->sentence(random_int(1, 6)),
            'confirm' => $this->faker->boolean,
            'is_primary' => $this->faker->boolean,
            'is_active' => $this->faker->boolean,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ];
    }
}
