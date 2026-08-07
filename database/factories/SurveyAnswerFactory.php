<?php

namespace Database\Factories;

use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

class SurveyAnswerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SurveyAnswer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'survey_response_id' => fn () => SurveyResponse::factory()->create()->id,
            'survey_question_id' => fn () => SurveyQuestion::factory()->create()->id,
            'value_int' => null,
            'value_option' => null,
            'value_text' => $this->faker->sentence(),
        ];
    }

    public function rating(int $value): static
    {
        return $this->state(fn () => [
            'value_int' => $value,
            'value_option' => null,
            'value_text' => null,
        ]);
    }

    public function option(string $value): static
    {
        return $this->state(fn () => [
            'value_int' => null,
            'value_option' => $value,
            'value_text' => null,
        ]);
    }
}
