<?php

namespace Tests\Feature;

use App\Mail\DiscordPostFailure;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The suite must never deliver mail.
 *
 * This is a regression guard with a real incident behind it. phpunit.xml
 * pinned APP_ENV, CACHE_DRIVER, SESSION_DRIVER and QUEUE_DRIVER but said
 * nothing about the mailer, so the suite inherited the live mailgun
 * credentials from .env. Every test that drove a Discord permanent-failure
 * path called DiscordEventPoster::notifyAdmin() and emailed the admin for
 * real — 92 of them in one afternoon, which looked from the inbox like a
 * broken hourly cron on dev rather than the test suite.
 *
 * Two things make that impossible now: MAIL_DRIVER=array in phpunit.xml, and
 * Tests\TestCase::setUp() forcing the same at runtime. This asserts the
 * outcome rather than either mechanism, so it still holds if the way we get
 * there changes.
 */
class MailNeverLeavesTheSuiteTest extends TestCase
{
    public function test_the_configured_mail_transport_is_the_array_transport(): void
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
        // Belt and braces: even if something re-resolved a transport, the
        // suite must not be holding the production sender's configuration.
        $this->assertNotSame('mailgun', config('mail.driver'));
        $this->assertNotSame('smtp', config('mail.driver'));
    }
}
