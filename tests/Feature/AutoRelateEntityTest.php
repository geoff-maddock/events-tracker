<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Series;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers PagesController::autoRelateEntity — the admin-only action that
 * relates an entity to every event/series whose name, short or description
 * contains the exact keyword phrase (case-insensitive).
 *
 * Regression guard for the "Gun Ray" bug: search surfaces events via
 * FULLTEXT over descriptions, but the old auto-relate query only matched
 * event names, so clicking the link related nothing.
 */
class AutoRelateEntityTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function activeUser(): User
    {
        /** @var User $user */
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        return $user;
    }

    private function admin(): User
    {
        $admin = $this->activeUser();
        $admin->assignGroup('admin');

        return $admin;
    }

    private function makeEntity(): Entity
    {
        return Entity::factory()->create(['name' => 'Gun Ray', 'slug' => 'gun-ray']);
    }

    /** @test */
    public function guest_cannot_auto_relate(): void
    {
        $entity = $this->makeEntity();
        $event = Event::factory()->create([
            'name'          => 'Dance Night',
            'description'   => 'Lineup: Gun Ray, Someone Else',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        $this->get(route('pages.relate', ['id' => $entity->id, 'keyword' => 'gun ray']));

        $this->assertDatabaseMissing('entity_event', ['event_id' => $event->id, 'entity_id' => $entity->id]);
    }

    /** @test */
    public function non_admin_cannot_auto_relate(): void
    {
        $entity = $this->makeEntity();
        $event = Event::factory()->create([
            'name'          => 'Dance Night',
            'description'   => 'Lineup: Gun Ray, Someone Else',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        $this->actingAs($this->activeUser())
            ->get(route('pages.relate', ['id' => $entity->id, 'keyword' => 'gun ray']));

        $this->assertDatabaseMissing('entity_event', ['event_id' => $event->id, 'entity_id' => $entity->id]);
    }

    /** @test */
    public function admin_relates_events_matching_exact_phrase_case_insensitively(): void
    {
        $entity = $this->makeEntity();

        $byName = Event::factory()->create([
            'name'          => 'GUN RAY Album Release',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);
        $byDescription = Event::factory()->create([
            'name'          => 'Dance Night',
            'description'   => 'Lineup: gun ray, Someone Else',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);
        // Contains both words but not the exact phrase — must NOT match.
        $splitWords = Event::factory()->create([
            'name'          => 'Machine Gun, Idle Ray',
            'description'   => 'Two unrelated acts',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('pages.relate', ['id' => $entity->id, 'keyword' => 'Gun Ray']));

        $response->assertRedirect(route('pages.search', ['keyword' => 'Gun Ray']));
        $this->assertDatabaseHas('entity_event', ['event_id' => $byName->id, 'entity_id' => $entity->id]);
        $this->assertDatabaseHas('entity_event', ['event_id' => $byDescription->id, 'entity_id' => $entity->id]);
        $this->assertDatabaseMissing('entity_event', ['event_id' => $splitWords->id, 'entity_id' => $entity->id]);
    }

    /** @test */
    public function admin_relates_matching_series(): void
    {
        $entity = $this->makeEntity();
        $series = Series::factory()->create([
            'name'          => 'Monthly Party',
            'description'   => 'Residents: Gun Ray and friends',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        $this->actingAs($this->admin())
            ->get(route('pages.relate', ['id' => $entity->id, 'keyword' => 'gun ray']));

        $this->assertDatabaseHas('entity_series', ['series_id' => $series->id, 'entity_id' => $entity->id]);
    }

    /** @test */
    public function relating_twice_does_not_duplicate_pivot_rows(): void
    {
        $entity = $this->makeEntity();
        $event = Event::factory()->create([
            'name'          => 'Gun Ray Live',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        $admin = $this->admin();
        $this->actingAs($admin)->get(route('pages.relate', ['id' => $entity->id, 'keyword' => 'gun ray']));
        $this->actingAs($admin)->get(route('pages.relate', ['id' => $entity->id, 'keyword' => 'gun ray']));

        $this->assertSame(
            1,
            \DB::table('entity_event')->where(['event_id' => $event->id, 'entity_id' => $entity->id])->count()
        );
    }

    /** @test */
    public function other_users_private_events_are_not_related(): void
    {
        $entity = $this->makeEntity();
        $owner = $this->activeUser();
        $privateEvent = Event::factory()->create([
            'name'          => 'Gun Ray Secret Show',
            'visibility_id' => Visibility::VISIBILITY_PRIVATE,
            'created_by'    => $owner->id,
        ]);

        $this->actingAs($this->admin())
            ->get(route('pages.relate', ['id' => $entity->id, 'keyword' => 'gun ray']));

        $this->assertDatabaseMissing('entity_event', ['event_id' => $privateEvent->id, 'entity_id' => $entity->id]);
    }

    /** @test */
    public function empty_keyword_relates_nothing(): void
    {
        $entity = $this->makeEntity();
        $event = Event::factory()->create([
            'name'          => 'Gun Ray Live',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        $this->actingAs($this->admin())
            ->get(route('pages.relate', ['id' => $entity->id, 'keyword' => '']));

        $this->assertDatabaseMissing('entity_event', ['event_id' => $event->id, 'entity_id' => $entity->id]);
    }
}
