<?php

namespace Tests\Feature;

use App\Mail\NewsletterConfirmSubscription;
use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NewsletterSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withExceptionHandling();

        // the array cache rate limiter persists across tests in one process
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    public function test_visitor_can_subscribe_with_email_only(): void
    {
        Mail::fake();

        $response = $this->post(route('newsletter.subscribe'), [
            'email' => 'subscriber@example.com',
            'source' => 'homepage',
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'subscriber@example.com',
            'source' => 'homepage',
            'confirmed_at' => null,
        ]);
        Mail::assertSent(NewsletterConfirmSubscription::class, function ($mail) {
            return $mail->hasTo('subscriber@example.com');
        });
    }

    public function test_invalid_email_is_rejected(): void
    {
        Mail::fake();

        $response = $this->post(route('newsletter.subscribe'), ['email' => 'not-an-email']);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('newsletter_subscribers', 0);
        Mail::assertNothingSent();
    }

    public function test_honeypot_field_blocks_bots_silently(): void
    {
        Mail::fake();

        $response = $this->post(route('newsletter.subscribe'), [
            'email' => 'bot@example.com',
            'website' => 'https://spam.example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseCount('newsletter_subscribers', 0);
        Mail::assertNothingSent();
    }

    public function test_already_confirmed_subscriber_is_not_duplicated_or_remailed(): void
    {
        Mail::fake();

        $subscriber = NewsletterSubscriber::factory()->confirmed()->create();

        $response = $this->post(route('newsletter.subscribe'), ['email' => $subscriber->email]);

        $response->assertRedirect();
        $this->assertDatabaseCount('newsletter_subscribers', 1);
        Mail::assertNothingSent();
    }

    public function test_confirm_link_activates_subscription(): void
    {
        $subscriber = NewsletterSubscriber::factory()->create();

        $response = $this->get(route('newsletter.confirm', ['token' => $subscriber->token]));

        $response->assertOk();
        $response->assertSee($subscriber->email);
        $this->assertNotNull($subscriber->fresh()->confirmed_at);
    }

    public function test_confirm_with_bad_token_returns_404(): void
    {
        $this->get(route('newsletter.confirm', ['token' => str_repeat('x', 64)]))
            ->assertNotFound();
    }

    public function test_unsubscribe_link_deactivates_subscription(): void
    {
        $subscriber = NewsletterSubscriber::factory()->confirmed()->create();

        $response = $this->get(route('newsletter.unsubscribe', ['token' => $subscriber->token]));

        $response->assertOk();
        $this->assertNotNull($subscriber->fresh()->unsubscribed_at);
    }

    public function test_one_click_unsubscribe_post_works_without_csrf_token(): void
    {
        $subscriber = NewsletterSubscriber::factory()->confirmed()->create();

        // RFC 8058 one-click POST from a mail provider carries no CSRF token
        $response = $this->post(route('newsletter.unsubscribe', ['token' => $subscriber->token]), [
            'List-Unsubscribe' => 'One-Click',
        ]);

        $response->assertOk();
        $this->assertNotNull($subscriber->fresh()->unsubscribed_at);
    }

    public function test_resubscribe_after_unsubscribe_requires_fresh_confirmation(): void
    {
        Mail::fake();

        $subscriber = NewsletterSubscriber::factory()->unsubscribed()->create();

        $response = $this->post(route('newsletter.subscribe'), ['email' => $subscriber->email]);

        $response->assertRedirect();
        $subscriber = $subscriber->fresh();
        $this->assertNull($subscriber->unsubscribed_at);
        $this->assertNull($subscriber->confirmed_at);
        Mail::assertSent(NewsletterConfirmSubscription::class, function ($mail) use ($subscriber) {
            return $mail->hasTo($subscriber->email);
        });
    }

    public function test_email_is_normalized_to_lowercase(): void
    {
        Mail::fake();

        $this->post(route('newsletter.subscribe'), ['email' => 'MixedCase@Example.COM']);

        $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'mixedcase@example.com']);
    }
}
