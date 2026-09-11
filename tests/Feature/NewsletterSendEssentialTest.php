<?php

namespace Tests\Feature;

use App\Mail\EssentialEventsDigest;
use App\Models\Event;
use App\Models\NewsletterSubscriber;
use App\Models\Visibility;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NewsletterSendEssentialTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function createEssentialEvent(array $attributes = []): Event
    {
        return Event::factory()->create(array_merge([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'is_essential' => true,
            'cancelled_at' => null,
            'start_at' => Carbon::now()->addDays(2),
            'end_at' => Carbon::now()->addDays(2)->addHour(),
        ], $attributes));
    }

    public function test_digest_only_sent_to_confirmed_subscribers(): void
    {
        Mail::fake();

        $this->createEssentialEvent();

        $confirmed = NewsletterSubscriber::factory()->confirmed()->create();
        $unconfirmed = NewsletterSubscriber::factory()->create();
        $unsubscribed = NewsletterSubscriber::factory()->unsubscribed()->create();

        $exitCode = Artisan::call('newsletter:send-essential');

        $this->assertEquals(0, $exitCode);
        Mail::assertSent(EssentialEventsDigest::class, function ($mail) use ($confirmed) {
            return $mail->hasTo($confirmed->email);
        });
        Mail::assertNotSent(EssentialEventsDigest::class, function ($mail) use ($unconfirmed) {
            return $mail->hasTo($unconfirmed->email);
        });
        Mail::assertNotSent(EssentialEventsDigest::class, function ($mail) use ($unsubscribed) {
            return $mail->hasTo($unsubscribed->email);
        });
    }

    public function test_digest_includes_only_upcoming_essential_events(): void
    {
        Mail::fake();

        $essential = $this->createEssentialEvent();
        $nonEssential = $this->createEssentialEvent(['is_essential' => false]);
        $tooFarOut = $this->createEssentialEvent(['start_at' => Carbon::now()->addDays(30)]);
        $private = $this->createEssentialEvent(['visibility_id' => Visibility::VISIBILITY_PRIVATE]);

        NewsletterSubscriber::factory()->confirmed()->create();

        Artisan::call('newsletter:send-essential');

        Mail::assertSent(EssentialEventsDigest::class, function ($mail) use ($essential, $nonEssential, $tooFarOut, $private) {
            $ids = $mail->events->pluck('id');

            return $ids->contains($essential->id)
                && !$ids->contains($nonEssential->id)
                && !$ids->contains($tooFarOut->id)
                && !$ids->contains($private->id);
        });
    }

    public function test_no_digest_sent_when_no_essential_events(): void
    {
        Mail::fake();

        $this->createEssentialEvent(['is_essential' => false]);
        NewsletterSubscriber::factory()->confirmed()->create();

        $exitCode = Artisan::call('newsletter:send-essential');

        $this->assertEquals(0, $exitCode);
        Mail::assertNothingSent();
    }

    public function test_digest_carries_unsubscribe_headers(): void
    {
        Mail::fake();

        $this->createEssentialEvent();
        $subscriber = NewsletterSubscriber::factory()->confirmed()->create();

        Artisan::call('newsletter:send-essential');

        Mail::assertSent(EssentialEventsDigest::class, function ($mail) use ($subscriber) {
            $headers = $mail->headers()->text;

            return str_contains($headers['List-Unsubscribe'], $subscriber->token)
                && $headers['List-Unsubscribe-Post'] === 'List-Unsubscribe=One-Click';
        });
    }
}
