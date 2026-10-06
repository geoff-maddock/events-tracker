<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Series;
use App\Models\Thread;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The visibility scopes compared created_by to the viewer's id, and for a guest
 * that id is null: `created_by = null` compiles to `created_by IS NULL`. Series
 * and threads keep created_by nullable (ON DELETE SET NULL), so deleting a user
 * exposed their private and proposed series and threads to every guest. Events
 * can't be orphaned (NOT NULL, and a deleted user's events pass to the admin),
 * but their scopes had the same clause.
 */
class OrphanedPrivateContentVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const ORPHAN = 'Zz Orphaned Private';

    public function test_guests_do_not_see_private_series_or_threads_whose_creator_was_deleted(): void
    {
        $this->withExceptionHandling();
        $creator = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $series = Series::factory()->create(['name' => self::ORPHAN.' Series', 'visibility_id' => Visibility::VISIBILITY_PRIVATE, 'created_by' => $creator->id]);
        $thread = Thread::factory()->create(['name' => self::ORPHAN.' Thread', 'visibility_id' => Visibility::VISIBILITY_PRIVATE, 'created_by' => $creator->id]);
        $proposal = Series::factory()->create(['visibility_id' => Visibility::VISIBILITY_PROPOSAL, 'created_by' => $creator->id]);

        // the creator is deleted: the foreign keys set created_by to null
        DB::table('series')->whereIn('id', [$series->id, $proposal->id])->update(['created_by' => null]);
        DB::table('threads')->where('id', $thread->id)->update(['created_by' => null]);

        $this->assertNotContains($series->id, Series::visible(null)->pluck('series.id'));
        $this->assertNotContains($proposal->id, Series::visible(null)->pluck('series.id'));
        $this->assertNotContains($thread->id, Thread::visible(null)->pluck('id'));

        $this->get('/series')->assertOk()->assertDontSee(self::ORPHAN);
        $this->get('/threads')->assertOk()->assertDontSee(self::ORPHAN);
    }

    public function test_owners_still_see_their_own_private_series_and_threads(): void
    {
        $owner = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $series = Series::factory()->create(['visibility_id' => Visibility::VISIBILITY_PRIVATE, 'created_by' => $owner->id]);
        $thread = Thread::factory()->create(['visibility_id' => Visibility::VISIBILITY_PRIVATE, 'created_by' => $owner->id]);
        $guarded = Series::factory()->create(['visibility_id' => Visibility::VISIBILITY_GUARDED]);
        // the attribution trait sets created_by from the signed-in user on create
        DB::table('threads')->where('id', $thread->id)->update(['created_by' => $owner->id]);

        $this->assertContains($series->id, Series::visible($owner)->pluck('series.id'));
        $this->assertContains($guarded->id, Series::visible($owner)->pluck('series.id'));
        $this->assertContains($thread->id, Thread::visible($owner)->pluck('id'));
        $this->assertNotContains($guarded->id, Series::visible(null)->pluck('series.id'));
    }

    public function test_a_guests_visibility_query_never_compares_created_by(): void
    {
        foreach ([
            'Event::visible' => Event::visible(null),
            'Event::starting' => Event::starting(now()->format('Y-m-d')),
            'Series::visible' => Series::visible(null),
            'Thread::visible' => Thread::visible(null),
        ] as $scope => $query) {
            $this->assertStringNotContainsString('created_by', $query->toSql(), $scope);
        }
    }
}
