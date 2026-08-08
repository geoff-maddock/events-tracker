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
     * Committed sample images used as upload fixtures.
     *
     * Deliberately not /tmp. faker->file() copies a *random* file out of the
     * source directory into a web-served path, so a world-writable source
     * means anything sitting in /tmp gets published under public/storage.
     */
    private const FIXTURE_DIR = __DIR__ . '/fixtures/photos';

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
            'thumbnail' => $this->faker->file(self::FIXTURE_DIR, $path),
            'path' => $this->faker->file(self::FIXTURE_DIR, $path),
            'caption' => $this->faker->sentence,
            'is_public' => $this->faker->boolean,
            'is_primary' => $this->faker->boolean,
            'is_event' => $this->faker->boolean,
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
