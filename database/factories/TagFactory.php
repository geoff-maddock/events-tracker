<?php

namespace Database\Factories;

use App\Models\Tag;
use App\Models\TagType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TagFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Tag::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'slug' => Str::slug($this->faker->word, '_'),
            'tag_type_id' => function () {
                return TagType::all()->random()->id;
            },
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ];
    }
}
