<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

/**
 * The invite flow is one of the sends where the email IS the deliverable, so
 * a transport failure must be reported rather than swallowed. Previously the
 * TransportException escaped as a 500; swallowing it silently would be just as
 * wrong, because the success flash claims "An email was sent to ...".
 */
class InviteMailFailureTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function admin(): User
    {
        return User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
    }

    public function test_a_failed_invite_does_not_500(): void
    {
        Mail::shouldReceive('send')->andThrow(new TransportException('535 Authentication failed'));

        $response = $this->actingAs($this->admin())
            ->post(route('pages.invite'), ['email' => 'invitee@example.com']);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    public function test_a_failed_invite_is_not_reported_as_sent(): void
    {
        Mail::shouldReceive('send')->andThrow(new TransportException('535 Authentication failed'));

        $this->actingAs($this->admin())
            ->post(route('pages.invite'), ['email' => 'invitee@example.com']);

        $flash = session('flash_message');

        $this->assertNotNull($flash, 'expected a flash message after a failed invite');
        $this->assertSame('Email not sent', $flash['title']);
        $this->assertStringNotContainsString('was sent', $flash['message']);
    }

    public function test_a_successful_invite_still_reports_success(): void
    {
        Mail::fake();

        $this->actingAs($this->admin())
            ->post(route('pages.invite'), ['email' => 'invitee@example.com']);

        $flash = session('flash_message');

        $this->assertNotNull($flash);
        $this->assertSame('Success', $flash['title']);
    }
}
