<?php

namespace Database\Factories;

use App\Models\Photo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhotoFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Photo::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $path = public_path() . '/' . Photo::STORAGEDIR . '/' . Photo::BASEDIR;

        return [
            'name' => $this->faker->word,
            'thumbnail' => $this->faker->file('/tmp', $path),
            'path' => $this->faker->file('/tmp', $path),
            'caption' => $this->faker->sentence,
            'is_public' => $this->faker->boolean,
            'is_primary' => $this->faker->boolean,
            'is_approved' => $this->faker->boolean,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'created_by' => function () {
                return User::all()->random()->id;
            },
            'updated_by' => function () {
                return User::all()->random()->id;
            }
        ];
    }
}
