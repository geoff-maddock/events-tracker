<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Event;
use App\Models\EventResponse;
use App\Models\Follow;
use App\Models\ResponseType;
use App\Models\Series;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A double submit used to add a second follow or attending row, and unfollowing
 * then removed only one of them, so the user stayed followed (#2341).
 */
class FollowAndAttendDuplicatesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();

        $this->user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->actingAs($this->user);
    }

    private function follows(string $type, int $id): int
    {
        return Follow::where('object_type', $type)->where('object_id', $id)->where('user_id', $this->user->id)->count();
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function followables(): array
    {
        return [
            'series' => ['series', 'series'],
            'tag' => ['tag', 'tags'],
            'thread' => ['thread', 'threads'],
            'entity' => ['entity', 'entities'],
        ];
    }

    private function makeFollowable(string $type): int
    {
        return match ($type) {
            'series' => Series::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC])->id,
            'tag' => Tag::factory()->create()->id,
            'thread' => Thread::factory()->create()->id,
            default => Entity::factory()->create()->id,
        };
    }

    /**
     * @dataProvider followables
     */
    public function test_following_twice_leaves_one_row(string $type, string $prefix): void
    {
        $id = $this->makeFollowable($type);

        $this->post("/{$prefix}/{$id}/follow");
        $this->post("/{$prefix}/{$id}/follow");

        $this->assertSame(1, $this->follows($type, $id));
    }

    /**
     * @dataProvider followables
     */
    public function test_unfollowing_removes_duplicate_rows(string $type, string $prefix): void
    {
        $id = $this->makeFollowable($type);
        foreach ([1, 2] as $copy) {
            Follow::create(['object_type' => $type, 'object_id' => $id, 'user_id' => $this->user->id]);
        }

        $this->post("/{$prefix}/{$id}/unfollow");

        $this->assertSame(0, $this->follows($type, $id));
    }

    public function test_attending_twice_leaves_one_row_and_unattending_removes_all(): void
    {
        $event = Event::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        $attending = fn () => EventResponse::where('event_id', $event->id)->where('user_id', $this->user->id)->where('response_type_id', ResponseType::ATTENDING)->count();

        $this->post("/events/{$event->id}/attend");
        $this->post("/events/{$event->id}/attend");
        $this->assertSame(1, $attending());

        EventResponse::create(['event_id' => $event->id, 'user_id' => $this->user->id, 'response_type_id' => ResponseType::ATTENDING]);
        $this->post("/events/{$event->id}/unattend");
        $this->assertSame(0, $attending());
    }
}
