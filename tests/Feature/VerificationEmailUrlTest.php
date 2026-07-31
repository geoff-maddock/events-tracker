<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Tests\TestCase;

/**
 * The email link base comes from app.frontend_url, which must fall back to
 * the app URL when no separate frontend origin is configured. The framework
 * default for frontend_url is http://localhost:3000, which leaked into
 * production emails when FRONTEND_URL was unset (see config/app.php).
 */
class VerificationEmailUrlTest extends TestCase
{
    protected function makeUnsavedUser(): User
    {
        $user = new User();
        $user->id = 12345;
        $user->email = 'verify-url-test@example.com';

        return $user;
    }

    public function testVerificationUrlFallsBackToAppUrlWhenNoFrontendUrlConfigured(): void
    {
        $mail = (new VerifyEmail())->toMail($this->makeUnsavedUser());

        $this->assertStringStartsWith(
            rtrim(config('app.url'), '/').'/email/verify/',
            $mail->actionUrl
        );
    }

    public function testVerificationUrlUsesConfiguredFrontendUrl(): void
    {
        config(['app.frontend_url' => 'https://spa.example.com']);

        $mail = (new VerifyEmail())->toMail($this->makeUnsavedUser());

        $this->assertStringStartsWith('https://spa.example.com/email/verify/', $mail->actionUrl);
    }

    public function testResetPasswordUrlFallsBackToAppUrlWhenNoFrontendUrlConfigured(): void
    {
        $mail = (new ResetPassword('dummy-token'))->toMail($this->makeUnsavedUser());

        $this->assertStringStartsWith(
            rtrim(config('app.url'), '/').'/password/reset/dummy-token',
            $mail->actionUrl
        );
    }
}
