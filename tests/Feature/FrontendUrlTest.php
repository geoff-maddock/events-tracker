<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FrontendUrl;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Emailed auth links only use a client-supplied frontend-url when its origin
 * is allowlisted; anything else falls back to app.frontend_url.
 */
class FrontendUrlTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.url' => 'https://app.example.com',
            'app.frontend_url' => 'https://app.example.com',
            'app.allowed_frontend_urls' => ['https://spa.example.com'],
        ]);
    }

    public function test_resolve_accepts_allowlisted_origins(): void
    {
        $this->assertSame('https://spa.example.com', FrontendUrl::resolve('https://spa.example.com/'));
        // origin comparison is case-insensitive; the URL is returned as given
        $this->assertSame('https://APP.example.com/sub', FrontendUrl::resolve('https://APP.example.com/sub/'));
    }

    public function test_resolve_rejects_other_origins(): void
    {
        foreach ([
            'https://evil.example',
            'https://spa.example.com.evil.example',
            'https://spa.example.com@evil.example',
            'http://spa.example.com',
            'https://spa.example.com:8443',
            'javascript:alert(1)',
            '//evil.example',
            'not a url',
            '',
            null,
        ] as $candidate) {
            $this->assertSame('https://app.example.com', FrontendUrl::resolve($candidate), var_export($candidate, true));
        }
    }

    public function test_reset_link_ignores_foreign_frontend_url(): void
    {
        $user = User::factory()->create();
        $this->app->instance('request', Request::create('/password/email', 'POST', [
            'email' => $user->email,
            'frontend-url' => 'https://evil.example',
        ]));

        $url = (new ResetPassword('tok123'))->toMail($user)->actionUrl;

        $this->assertStringStartsWith('https://app.example.com/password/reset/tok123', $url);
    }

    public function test_reset_link_uses_allowlisted_frontend_url(): void
    {
        $user = User::factory()->create();
        $this->app->instance('request', Request::create('/api/user/send-password-reset-email', 'POST', [
            'frontend-url' => 'https://spa.example.com',
        ]));

        $url = (new ResetPassword('tok123'))->toMail($user)->actionUrl;

        $this->assertStringStartsWith('https://spa.example.com/password/reset/tok123', $url);
    }

    public function test_verification_link_ignores_foreign_frontend_url(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $user->frontendUrl = 'https://evil.example';

        $url = (new VerifyEmail())->toMail($user)->actionUrl;

        $this->assertStringStartsWith('https://app.example.com/', $url);
    }
}
