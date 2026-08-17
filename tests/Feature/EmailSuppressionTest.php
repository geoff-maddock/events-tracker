<?php

namespace Tests\Feature;

use App\Models\EmailSuppression;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Suppression enforcement.
 *
 * The app never consumed the ESP's bounce data, so addresses that hard-bounced
 * years ago were still mailed on every digest run — 24 of the 158 Mailgun
 * bounces are live users with all notification settings on. Enforcement hangs
 * off MessageSending rather than the send call sites, because that is the only
 * hook the console digests also pass through.
 *
 * NOTE: these tests deliberately do NOT use Mail::fake(). The fake swaps out
 * the Mailer, so MessageSending never fires and the listener never runs —
 * every assertion would pass vacuously. phpunit.xml sets the array mailer, so
 * we drive the real Mailer and inspect the transport instead.
 */
class EmailSuppressionTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::getSymfonyTransport()->flush();
    }

    /** @return \Illuminate\Support\Collection<int, \Symfony\Component\Mailer\SentMessage> */
    private function sentMessages(): \Illuminate\Support\Collection
    {
        return Mail::getSymfonyTransport()->messages();
    }

    private function send(array $to, string $subject = 'Test'): void
    {
        Mail::raw('body', function ($m) use ($to, $subject) {
            $m->to($to)->subject($subject);
        });
    }

    /** @return array<int, string> */
    private function recipientsOfFirstMessage(): array
    {
        $messages = $this->sentMessages();
        $this->assertNotEmpty($messages, 'expected at least one message');

        $to = $messages->first()->getOriginalMessage()->getTo();

        return array_map(static fn ($a) => $a->getAddress(), $to);
    }

    public function test_the_listener_actually_runs_and_lets_normal_mail_through(): void
    {
        $this->send(['fine@example.com']);

        // Guards the rest of this file: if the harness stopped exercising the
        // real Mailer, this fails instead of the suppression tests silently
        // passing on zero sends.
        $this->assertCount(1, $this->sentMessages());
    }

    public function test_a_suppressed_address_is_not_mailed(): void
    {
        EmailSuppression::suppress('bounced@example.com', EmailSuppression::REASON_BOUNCE, 'mailgun');

        $this->send(['bounced@example.com']);

        $this->assertCount(0, $this->sentMessages());
    }

    public function test_suppression_is_case_and_whitespace_insensitive(): void
    {
        EmailSuppression::suppress('  Bounced@Example.COM ', EmailSuppression::REASON_BOUNCE, 'mailgun');

        $this->send(['BOUNCED@example.com']);

        $this->assertCount(0, $this->sentMessages());
    }

    public function test_a_mixed_recipient_list_keeps_the_deliverable_addresses(): void
    {
        EmailSuppression::suppress('bounced@example.com', EmailSuppression::REASON_BOUNCE, 'mailgun');

        $this->send(['bounced@example.com', 'fine@example.com']);

        // One bad address in a batch must not cancel the whole message.
        $this->assertCount(1, $this->sentMessages());
        $this->assertSame(['fine@example.com'], $this->recipientsOfFirstMessage());
    }

    public function test_suppress_keeps_the_original_reason(): void
    {
        EmailSuppression::suppress('a@example.com', EmailSuppression::REASON_BOUNCE, 'mailgun');
        EmailSuppression::suppress('a@example.com', EmailSuppression::REASON_COMPLAINT, 'ses');

        $row = EmailSuppression::query()->where('email', 'a@example.com')->get();

        $this->assertCount(1, $row);
        $this->assertSame(EmailSuppression::REASON_BOUNCE, $row->first()->reason);
    }

    public function test_is_suppressed_reflects_writes_immediately(): void
    {
        $this->assertFalse(EmailSuppression::isSuppressed('later@example.com'));

        EmailSuppression::suppress('later@example.com', EmailSuppression::REASON_BOUNCE, 'mailgun');

        // Deliberately not memoised — the queue worker is long-lived and a
        // cached "not suppressed" would outlive the bounce that changed it.
        $this->assertTrue(EmailSuppression::isSuppressed('later@example.com'));
    }
}
