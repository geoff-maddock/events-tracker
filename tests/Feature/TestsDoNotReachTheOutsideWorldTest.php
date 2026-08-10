<?php

namespace Tests\Feature;

use App\Mail\DiscordPostFailure;
use Illuminate\Http\Client\StrayRequestException;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The suite must not deliver mail or reach the network.
 *
 * There is a real incident behind the mail half. phpunit.xml pinned APP_ENV,
 * CACHE_DRIVER, SESSION_DRIVER and QUEUE_DRIVER but said nothing about the
 * mailer, so the suite inherited the live mailgun credentials from .env. Every
 * test that drove a Discord permanent-failure path called
 * DiscordEventPoster::notifyAdmin() and emailed the admin for real — 92 in one
 * afternoon, which from the inbox looked like a broken hourly cron on dev
 * rather than the test suite.
 *
 * The HTTP half is preventative rather than remedial: the Discord tests were
 * already faking their webhook calls, and the 400s and 404s in the log came
 * from those stubs, not from Discord. Blocking stray requests makes that a
 * property of the suite instead of a habit every future test has to remember.
 *
 * These assert the outcome rather than either mechanism, so they still hold if
 * the way we get there changes.
 */
class TestsDoNotReachTheOutsideWorldTest extends TestCase
{
    public function test_the_configured_mail_transport_cannot_deliver(): void
    {
        // config/mail.php is the legacy flat format; MailManager reads the
        // transport from mail.driver rather than mail.mailers.*.
        $this->assertSame('array', config('mail.driver'));

        $this->assertInstanceOf(
            ArrayTransport::class,
            Mail::mailer()->getSymfonyTransport(),
            'The suite is configured with a transport that can actually deliver mail.'
        );
    }

    public function test_a_mailable_is_collected_in_memory_rather_than_sent(): void
    {
        $transport = Mail::mailer()->getSymfonyTransport();
        $transport->flush();

        // The exact mailable from the incident.
        Mail::send(new DiscordPostFailure(
            'Some Channel',
            'https://discord.com/api/webhooks/123456789012345678/************',
            'Discord rejected the request (400): Invalid Form Body [code 50035]',
            false,
            'Test App',
            'admin@example.com',
            'noreply@example.com',
        ));

        // Rendered and captured, never transmitted. ArrayTransport is used in
        // preference to Mail::fake() precisely so the Blade template is still
        // exercised — a broken mailable must still fail its own test.
        $this->assertCount(1, $transport->messages());
    }

    public function test_the_real_mail_credentials_are_not_in_play(): void
    {
        $this->assertNotSame('mailgun', config('mail.driver'));
        $this->assertNotSame('smtp', config('mail.driver'));
    }

    public function test_an_unfaked_request_throws_instead_of_leaving_the_machine(): void
    {
        $this->assertTrue(Http::preventingStrayRequests());

        $this->expectException(StrayRequestException::class);

        Http::post('https://discord.com/api/webhooks/123456789012345678/token', ['content' => 'x']);
    }

    public function test_a_faked_request_still_works(): void
    {
        // The guard must not make legitimate stubbing harder — this is how a
        // test opts back in to exercising an outbound call.
        Http::fake(['*' => Http::response(['id' => '1'], 200)]);

        $this->assertSame(
            ['id' => '1'],
            Http::post('https://discord.com/api/webhooks/123456789012345678/token')->json()
        );
    }
}
