<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Photo;
use App\Models\User;
use App\Models\Visibility;
use App\Services\Integrations\Instagram;
use App\Services\Integrations\InstagramEventPoster;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class QueuedWeekendPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Start of the weekend window the service will compute when it runs
     * "today" — same Fri/Sat/Sun logic so tests pass on any weekday.
     */
    private function weekendFriday(): Carbon
    {
        $today = Carbon::today();

        if ($today->isFriday()) {
            return $today->startOfDay();
        }
        if ($today->isSaturday() || $today->isSunday()) {
            return $today->previous(Carbon::FRIDAY)->startOfDay();
        }

        return $today->next(Carbon::FRIDAY)->startOfDay();
    }

    private function weekendEvent(User $user, bool $withPhoto = true, int $hour = 20): Event
    {
        $event = Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'created_by' => $user->id,
            'start_at' => $this->weekendFriday()->copy()->addHours($hour),
            'cancelled_at' => null,
        ]);

        if ($withPhoto) {
            $photo = Photo::factory()->create([
                'is_primary' => 1,
                'path' => 'test.jpg',
                'thumbnail' => 'test_thumb.jpg',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
            $event->photos()->attach($photo->id);
        }

        return $event;
    }

    private function mockStoryPostingInstagram(): Instagram|Mockery\MockInterface
    {
        Storage::shouldReceive('disk')->with('external')->andReturnSelf()->byDefault();
        Storage::shouldReceive('url')->andReturn('http://example.com/test.jpg')->byDefault();

        $instagram = Mockery::mock(Instagram::class);
        $instagram->shouldReceive('getIgUserId')->andReturn(123)->byDefault();
        $instagram->shouldReceive('getPageAccessToken')->andReturn('token')->byDefault();
        $instagram->shouldReceive('uploadStoryPhoto')->andReturn(111)->byDefault();
        $instagram->shouldReceive('checkStatus')->andReturn(true)->byDefault();
        $instagram->shouldReceive('publishStoryMedia')->andReturn(555)->byDefault();

        return $instagram;
    }

    public function test_service_throws_when_no_weekend_events_exist(): void
    {
        $poster = new InstagramEventPoster($this->mockStoryPostingInstagram());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No events found for the upcoming weekend.');

        $poster->postWeekendPreview(null);
    }

    public function test_service_posts_stories_and_skips_events_without_photos(): void
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $withPhoto = $this->weekendEvent($user, true, 20);
        $this->weekendEvent($user, false, 22); // no photo -> skipped

        $poster = new InstagramEventPoster($this->mockStoryPostingInstagram());

        $result = $poster->postWeekendPreview($user->id);

        $this->assertSame(['posted' => 1, 'skipped' => 1, 'total' => 2], $result);
        $this->assertDatabaseHas('event_shares', [
            'event_id' => $withPhoto->id,
            'platform' => 'instagram',
            'platform_id' => '555',
        ]);
    }

    public function test_service_throws_when_zero_stories_post(): void
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $this->weekendEvent($user, false, 20); // only event has no photo

        $poster = new InstagramEventPoster($this->mockStoryPostingInstagram());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No stories could be posted. Ensure the selected events have photos.');

        $poster->postWeekendPreview($user->id);
    }
}
