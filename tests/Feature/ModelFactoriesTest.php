<?php

namespace Tests\Feature;

use App\Models\ClickTrack;
use App\Models\Comment;
use App\Models\EmailSuppression;
use App\Models\EntityClaim;
use App\Models\Event;
use App\Models\EventReview;
use App\Models\EventShowing;
use App\Models\Follow;
use App\Models\Like;
use App\Models\Series;
use App\Models\ShortUrl;
use App\Models\Tag;
use App\Models\Thread;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The factories added for models that had none (#2404): each makes a row the
 * database accepts, and the ones with a subject point at it the way the app does.
 */
class ModelFactoriesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /**
     * @return array<string, array{\Closure(): Factory<covariant Model>}>
     */
    public static function factories(): array
    {
        return [
            'comment' => [fn () => Comment::factory()],
            'follow' => [fn () => Follow::factory()],
            'like' => [fn () => Like::factory()],
            'event showing' => [fn () => EventShowing::factory()],
            'entity claim' => [fn () => EntityClaim::factory()],
            'event review' => [fn () => EventReview::factory()],
            'short url' => [fn () => ShortUrl::factory()],
            'click track' => [fn () => ClickTrack::factory()],
            'email suppression' => [fn () => EmailSuppression::factory()],
        ];
    }

    /**
     * @param \Closure(): Factory<covariant Model> $factory
     */
    #[Test]
    #[DataProvider('factories')]
    public function a_factory_makes_rows_the_database_accepts(\Closure $factory): void
    {
        $rows = $factory()->count(2)->create();

        $this->assertCount(2, $rows);
        $this->assertSame(2, $rows->first()->newQuery()->whereKey($rows->modelKeys())->count());
    }

    #[Test]
    public function every_factory_belongs_to_a_model_that_can_use_it(): void
    {
        foreach (File::files(database_path('factories')) as $file) {
            $model = 'App\\Models\\'.substr($file->getFilename(), 0, -strlen('Factory.php'));

            $this->assertTrue(method_exists($model, 'factory'), $model.' has a factory file but not the HasFactory trait');
        }
    }

    #[Test]
    public function a_follow_or_like_is_stored_under_its_subjects_alias(): void
    {
        $tag = Tag::factory()->create();
        $series = Series::factory()->create();
        $thread = Thread::factory()->create();

        $this->assertTrue($tag->follows()->whereKey(Follow::factory()->of($tag)->create()->id)->exists());
        $this->assertTrue($series->follows()->whereKey(Follow::factory()->of($series)->create()->id)->exists());
        $this->assertTrue($thread->follows()->whereKey(Follow::factory()->of($thread)->create()->id)->exists());

        $follow = Follow::factory()->create();
        $this->assertSame('entity', $follow->object_type);
        $this->assertTrue($follow->user()->exists());

        // likes are kept for threads and posts
        $this->assertSame('thread', Like::factory()->create()->object_type);
        $like = Like::factory()->of($thread)->create();
        $this->assertSame(['thread', $thread->id], [$like->object_type, $like->object_id]);
    }

    #[Test]
    public function a_comment_belongs_to_its_subject_and_author(): void
    {
        $thread = Thread::factory()->create();
        $comment = Comment::factory()->on($thread)->create();

        $this->assertTrue($comment->commentable->is($thread));
        $this->assertDatabaseHas('users', ['id' => $comment->created_by]);
        $this->assertInstanceOf(Event::class, Comment::factory()->create()->commentable);
    }

    #[Test]
    public function a_showing_moves_its_events_last_date(): void
    {
        $event = Event::factory()->create(['start_at' => now()->addDay()->setTime(20, 0)]);
        $later = EventShowing::factory()->create(['event_id' => $event->id, 'start_at' => now()->addDays(4)->setTime(20, 0)]);
        EventShowing::factory()->create(['event_id' => $event->id, 'start_at' => now()->addDays(2)->setTime(20, 0)]);

        $event->refresh();
        $this->assertTrue($event->last_start_at->equalTo($later->start_at));
        $this->assertTrue($event->isMultiDate());
        $this->assertCount(2, $event->showings);

        // left to itself a showing is the day after its event starts
        $default = EventShowing::factory()->create();
        $this->assertTrue($default->start_at->equalTo($default->event->start_at->copy()->addDay()));
    }

    #[Test]
    public function a_claim_can_be_made_already_reviewed(): void
    {
        $this->assertSame(EntityClaim::STATUS_PENDING, EntityClaim::factory()->create()->status);

        $approved = EntityClaim::factory()->approved()->create();
        $this->assertSame(EntityClaim::STATUS_APPROVED, $approved->status);
        $this->assertNotNull($approved->reviewed_at);
        $this->assertNotNull($approved->reviewed_by);
        $this->assertSame(EntityClaim::STATUS_DENIED, EntityClaim::factory()->denied()->create()->status);
    }

    #[Test]
    public function a_suppressed_address_is_stored_the_way_it_is_looked_up(): void
    {
        $suppression = EmailSuppression::factory()->create();

        $this->assertSame(EmailSuppression::normalize($suppression->email), $suppression->email);
        $this->assertSame(EmailSuppression::REASON_BOUNCE, $suppression->reason);
    }
}
