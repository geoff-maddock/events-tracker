<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\SurveyCampaign;
use App\Models\SurveyInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class SurveyInvitationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SurveyInvitation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'survey_campaign_id' => fn () => SurveyCampaign::factory()->create()->id,
            'user_id' => fn () => User::factory()->create()->id,
            'subject_type' => 'event',
            'subject_id' => fn () => Event::factory()->create()->id,
            'token' => SurveyInvitation::generateToken(),
            'status' => SurveyInvitation::STATUS_PENDING,
            'source' => SurveyInvitation::SOURCE_APP,
            'expires_at' => now()->addDays(30),
        ];
    }

    /**
     * Point the invitation at a specific model using the morph map.
     */
    public function about(Model $subject): static
    {
        return $this->state(fn () => [
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
        ]);
    }

    /**
     * No subject at all, e.g. a general interest poll.
     */
    public function subjectless(): static
    {
        return $this->state(fn () => ['subject_type' => '', 'subject_id' => 0]);
    }

    public function shown(): static
    {
        return $this->state(fn () => [
            'status' => SurveyInvitation::STATUS_SHOWN,
            'shown_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => SurveyInvitation::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function dismissed(): static
    {
        return $this->state(fn () => [
            'status' => SurveyInvitation::STATUS_DISMISSED,
            'dismissed_at' => now(),
        ]);
    }

    /**
     * Past its expiry but not yet swept by the generator.
     */
    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subDay()]);
    }

    public function snoozed(): static
    {
        return $this->state(fn () => ['snoozed_until' => now()->addWeek()]);
    }
}
