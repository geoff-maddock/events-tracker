<?php

namespace Database\Factories;

use App\Models\EmailSuppression;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EmailSuppression>
 */
class EmailSuppressionFactory extends Factory
{
    protected $model = EmailSuppression::class;

    /**
     * A hard bounce reported by the mail provider.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // stored the way EmailSuppression::normalize() would
            'email' => fn () => 'suppressed-'.str_replace('-', '', (string) Str::uuid()).'@example.test',
            'reason' => EmailSuppression::REASON_BOUNCE,
            'source' => 'manual',
            'code' => null,
            'error' => null,
            'suppressed_at' => now(),
        ];
    }
}
