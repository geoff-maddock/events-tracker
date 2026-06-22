<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\Event;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ensures the API listing endpoints apply the model `visible($user)`
 * scope so private/proposal content created by one user does not leak
 * into another authenticated user's listings.
 *
 * Previously Api\EventsController::index/::popular and
 * Api\BlogsController::index paginated without ->visible(), exposing
 * non-public rows. (The owner still sees their own private rows.)
 */
class ApiVisibilityFilteringTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $owner;
    private User $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->owner = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->other = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
    }

    /** @test */
    public function events_index_hides_other_users_private_events(): void
    {
        $private = Event::factory()->create([
            'created_by' => $this->owner->id,
            'name' => 'ZZVIS Private Event',
            'visibility_id' => Visibility::VISIBILITY_PRIVATE,
        ]);
        $public = Event::factory()->create([
            'created_by' => $this->owner->id,
            'name' => 'ZZVIS Public Event',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        // Another authenticated user must not see the private event.
        $this->actingAs($this->other, 'sanctum');
        $ids = collect($this->getJson('/api/events?filters[name]=ZZVIS&limit=100')->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($public->id), 'Public event should be listed.');
        $this->assertFalse($ids->contains($private->id), 'Private event must not leak to another user.');

        // The owner still sees their own private event.
        $this->actingAs($this->owner, 'sanctum');
        $ownerIds = collect($this->getJson('/api/events?filters[name]=ZZVIS&limit=100')->json('data'))->pluck('id');
        $this->assertTrue($ownerIds->contains($private->id), 'Owner should see their own private event.');
    }

    /** @test */
    public function events_popular_hides_other_users_private_events(): void
    {
        $private = Event::factory()->create([
            'created_by' => $this->owner->id,
            'name' => 'ZZPOP Private Event',
            'visibility_id' => Visibility::VISIBILITY_PRIVATE,
        ]);
        $public = Event::factory()->create([
            'created_by' => $this->owner->id,
            'name' => 'ZZPOP Public Event',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        $this->actingAs($this->other, 'sanctum');
        $ids = collect($this->getJson('/api/events/popular?filters[name]=ZZPOP&limit=100')->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($public->id), 'Public event should be listed.');
        $this->assertFalse($ids->contains($private->id), 'Private event must not leak via popular.');
    }

    /** @test */
    public function blogs_index_hides_other_users_private_blogs(): void
    {
        $private = Blog::factory()->create([
            'created_by' => $this->owner->id,
            'name' => 'ZZBLOG Private',
            'visibility_id' => Visibility::VISIBILITY_PRIVATE,
        ]);
        $public = Blog::factory()->create([
            'created_by' => $this->owner->id,
            'name' => 'ZZBLOG Public',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        $this->actingAs($this->other, 'sanctum');
        $ids = collect($this->getJson('/api/blogs?filters[name]=ZZBLOG&limit=100')->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($public->id), 'Public blog should be listed.');
        $this->assertFalse($ids->contains($private->id), 'Private blog must not leak to another user.');
    }
}
