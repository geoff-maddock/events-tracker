<?php

namespace Database\Factories;

use App\Models\NewsletterSubscriber;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class NewsletterSubscriberFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = NewsletterSubscriber::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'token' => Str::random(64),
            'source' => 'homepage',
        ];
    }

    public function confirmed(): self
    {
        return $this->state(fn () => ['confirmed_at' => now()]);
    }

    public function unsubscribed(): self
    {
        return $this->state(fn () => ['confirmed_at' => now(), 'unsubscribed_at' => now()]);
    }
}
