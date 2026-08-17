<?php

namespace Tests\Unit;

use App\Services\BestEffortMailer;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

/**
 * BestEffortMailer swallows mail-transport failures so that a dead SMTP server
 * cannot turn an already-committed action (event creation) into a 500.
 *
 * The breaker matters as much as the try/catch: with ~640 notification-enabled
 * users, catching per-recipient without a breaker would attempt one SMTP
 * connection per follower during an outage and hang the request instead.
 */
class BestEffortMailerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function mailable(): Mailable
    {
        return Mockery::mock(Mailable::class);
    }

    /**
     * Make every Mail::to(...)->send(...) throw a transport error, and count
     * how many times send() was actually reached.
     */
    private function failingMailer(): \Mockery\MockInterface
    {
        $pending = Mockery::mock();
        $pending->shouldReceive('send')
            ->andThrow(new TransportException('535 Authentication failed'));

        Mail::shouldReceive('to')->andReturn($pending);

        return $pending;
    }

    public function test_a_transport_failure_does_not_bubble_up(): void
    {
        $this->failingMailer();

        $mailer = new BestEffortMailer();
        $result = $mailer->send('someone@example.com', $this->mailable());

        $this->assertFalse($result);
        $this->assertSame(1, $mailer->summary()['failed']);
    }

    public function test_breaker_trips_after_the_failure_threshold(): void
    {
        $this->failingMailer();

        $mailer = new BestEffortMailer();
        for ($i = 0; $i < BestEffortMailer::MAX_CONSECUTIVE_FAILURES; ++$i) {
            $this->assertFalse($mailer->tripped(), "breaker tripped early on attempt {$i}");
            $mailer->send("user{$i}@example.com", $this->mailable());
        }

        $this->assertTrue($mailer->tripped());
    }

    public function test_sends_stop_hitting_the_transport_once_tripped(): void
    {
        $pending = Mockery::mock();
        // The transport must be reached exactly MAX_CONSECUTIVE_FAILURES times,
        // not once per recipient — this is the anti-hang guarantee.
        $pending->shouldReceive('send')
            ->times(BestEffortMailer::MAX_CONSECUTIVE_FAILURES)
            ->andThrow(new TransportException('535 Authentication failed'));

        Mail::shouldReceive('to')->andReturn($pending);

        $mailer = new BestEffortMailer();
        for ($i = 0; $i < 50; ++$i) {
            $mailer->send("user{$i}@example.com", $this->mailable());
        }

        $summary = $mailer->summary();
        $this->assertSame(BestEffortMailer::MAX_CONSECUTIVE_FAILURES, $summary['failed']);
        $this->assertSame(50 - BestEffortMailer::MAX_CONSECUTIVE_FAILURES, $summary['skipped']);
        $this->assertSame(0, $summary['sent']);
    }

    public function test_an_isolated_failure_does_not_trip_the_breaker(): void
    {
        $good = Mockery::mock();
        $good->shouldReceive('send')->andReturnNull();

        $bad = Mockery::mock();
        $bad->shouldReceive('send')->andThrow(new TransportException('550 no such recipient'));

        // one bad address between good ones — the run of failures never reaches
        // the threshold, so delivery continues
        Mail::shouldReceive('to')->andReturn($good, $bad, $good, $bad, $good);

        $mailer = new BestEffortMailer();
        foreach (range(1, 5) as $i) {
            $mailer->send("user{$i}@example.com", $this->mailable());
        }

        $this->assertFalse($mailer->tripped());
        $summary = $mailer->summary();
        $this->assertSame(3, $summary['sent']);
        $this->assertSame(2, $summary['failed']);
        $this->assertSame(0, $summary['skipped']);
    }

    public function test_attempt_swallows_failures_from_closure_style_sends(): void
    {
        $mailer = new BestEffortMailer();

        $result = $mailer->attempt(function (): void {
            throw new TransportException('535 Authentication failed');
        });

        $this->assertFalse($result);
        $this->assertSame(1, $mailer->summary()['failed']);
    }

    public function test_attempt_reports_success_for_closure_style_sends(): void
    {
        $mailer = new BestEffortMailer();

        $this->assertTrue($mailer->attempt(fn () => null));
        $this->assertSame(1, $mailer->summary()['sent']);
    }

    public function test_attempt_shares_the_breaker_with_send(): void
    {
        $mailer = new BestEffortMailer();

        for ($i = 0; $i < BestEffortMailer::MAX_CONSECUTIVE_FAILURES; ++$i) {
            $mailer->attempt(function (): void {
                throw new TransportException('535 Authentication failed');
            });
        }

        $this->assertTrue($mailer->tripped());

        // Once tripped the closure must not run again at all.
        $ran = false;
        $mailer->attempt(function () use (&$ran): void {
            $ran = true;
        });

        $this->assertFalse($ran);
        $this->assertSame(1, $mailer->summary()['skipped']);
    }

    public function test_successful_sends_are_counted_and_reported(): void
    {
        $pending = Mockery::mock();
        $pending->shouldReceive('send')->andReturnNull();
        Mail::shouldReceive('to')->andReturn($pending);

        $mailer = new BestEffortMailer();
        $this->assertTrue($mailer->send('someone@example.com', $this->mailable()));

        $this->assertSame(
            ['sent' => 1, 'failed' => 0, 'skipped' => 0, 'tripped' => false],
            $mailer->summary()
        );
    }
}
