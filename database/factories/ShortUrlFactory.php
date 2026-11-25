<?php

namespace Database\Factories;

use App\Models\ShortUrl;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ShortUrl>
 */
class ShortUrlFactory extends Factory
{
    protected $model = ShortUrl::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fn () => Str::lower(Str::random(8)),
            'url' => $this->faker->url(),
            'visit_count' => 0,
        ];
    }
}
