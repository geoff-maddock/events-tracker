<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Photo;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use App\Models\Entity;
use App\Models\EventType;
use App\Models\OccurrenceType;
use App\Models\UserStatus;
use App\Models\Visibility;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SeriesTest extends TestCase
{
    // refresh database and run migrations before test
    use RefreshDatabase;

    // reseed the database
    protected $seed = true;

    /**
     * Test trying to create a series with a user
     *
     * @return void
     */
    public function testCreateWithUser()
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now(), 'user_status_id' => UserStatus::ACTIVE]);

        $response = $this->actingAs($user)
            ->withSession([])
            ->get('/series/create');

        $response->assertStatus(200);
    }

    /**
     * Test trying to create a series with no user
     *
     * @return void
     */
    public function testCreateWithNoUser()
    {
        $response = $this->get('/series/create');

        $response->assertStatus(302);
    }

    /**
     * The /series/export feed must render even when a series has no start_at.
     *
     * @return void
     */
    public function testExportHandlesSeriesWithNullStartAt()
    {
        Series::factory()->create([
            'start_at' => null,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        $this->get('/series/export')->assertStatus(200);
    }

    /**
     * Check the series name appears on the series show page
     *
     * @return void
     */
    public function testShowSeries()
    {
        $user = User::factory()->create();
        $response = $this->get('/series/create');

        $response->assertStatus(302);
    }

    /**
     * Test that a series can be accessed by its slug
     *
     * @return void
     */
    public function testShowSeriesBySlug()
    {
        $series = Series::factory()->create(['slug' => 'test-series-slug']);

        $response = $this->get('/series/test-series-slug');

        $response->assertStatus(200);
        $response->assertSee($series->name);
    }

    /**
     * Test that a series can be accessed by its integer ID (backward compatibility)
     *
     * @return void
     */
    public function testShowSeriesById()
    {
        $series = Series::factory()->create(['slug' => 'test-series-by-id']);

        $response = $this->get('/series/' . $series->id);

        $response->assertStatus(200);
        $response->assertSee($series->name);
    }

    /**
     * Test creating a series from an event copies entities, tags and photos,
     * and allows an empty length (issue #1908).
     *
     * @return void
     */
    public function testCreateSeriesFromEventCopiesRelationsAndAllowsEmptyLength()
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now(), 'user_status_id' => UserStatus::ACTIVE]);

        $entity = Entity::factory()->create();
        $tag = Tag::factory()->create();
        $photo = Photo::factory()->create();

        $event = Event::factory()->create(['created_by' => $user->id]);
        $event->entities()->attach($entity->id);
        $event->tags()->attach($tag->id);
        $event->photos()->attach($photo->id);

        $occurrenceType = OccurrenceType::where('name', 'No Schedule')->first();
        $eventType = EventType::first();

        $response = $this->actingAs($user)
            ->withSession([])
            ->post('/series', [
                'name' => 'Series From Event',
                'slug' => 'series-from-event',
                'short' => 'A short description',
                'length' => '', // length must be optional
                'event_type_id' => $eventType->id,
                'visibility_id' => Visibility::VISIBILITY_PUBLIC,
                'occurrence_type_id' => $occurrenceType->id,
                'entity_list' => [$entity->id],
                'tag_list' => [$tag->id],
                'eventLinkId' => $event->id,
            ]);

        // No validation errors (notably length) and we are redirected to the new series
        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        $series = Series::where('slug', 'series-from-event')->firstOrFail();

        $this->assertNull($series->getRawOriginal('length'));
        $this->assertTrue($series->entities->contains($entity));
        $this->assertTrue($series->tags->contains($tag));
        $this->assertTrue($series->photos->contains($photo));

        // The source event is linked to the new series
        $this->assertEquals($series->id, $event->fresh()->series_id);
    }

    /**
     * Storing a series must bust and re-warm the form-opts-series cache so the
     * new series appears immediately in the event form series dropdown.
     *
     * @return void
     */
    public function testStoringSeriesRefreshesFormOptionsCache()
    {
        $user = User::factory()->create(['email_verified_at' => Carbon::now(), 'user_status_id' => UserStatus::ACTIVE]);

        $occurrenceType = OccurrenceType::where('name', 'No Schedule')->first();
        $eventType = EventType::first();

        // simulate a stale cached dropdown built before the series existed
        Cache::put('form-opts-series', [999999 => 'Stale Entry'], 3600);

        $response = $this->actingAs($user)
            ->withSession([])
            ->post('/series', [
                'name' => 'Ultraviolet',
                'slug' => 'ultraviolet',
                'short' => 'A new series',
                'event_type_id' => $eventType->id,
                'visibility_id' => Visibility::VISIBILITY_PUBLIC,
                'occurrence_type_id' => $occurrenceType->id,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        $series = Series::where('slug', 'ultraviolet')->firstOrFail();

        // cache was re-warmed (not just forgotten) and includes the new series
        $cached = Cache::get('form-opts-series');
        $this->assertNotNull($cached, 'form-opts-series cache should be re-warmed after storing a series');
        $this->assertArrayHasKey($series->id, $cached);
        $this->assertSame('Ultraviolet', $cached[$series->id]);
        $this->assertArrayNotHasKey(999999, $cached);
    }

    /**
     * Renaming a series must refresh the cached series dropdown options.
     *
     * @return void
     */
    public function testUpdatingSeriesRefreshesFormOptionsCache()
    {
        $series = Series::factory()->create(['name' => 'Old Name']);

        Cache::put('form-opts-series', [$series->id => 'Old Name'], 3600);

        $series->update(['name' => 'New Name']);

        $cached = Cache::get('form-opts-series');
        $this->assertNotNull($cached, 'form-opts-series cache should be re-warmed after updating a series');
        $this->assertSame('New Name', $cached[$series->id]);
    }

    /**
     * Deleting a series must refresh the cached series dropdown options.
     *
     * @return void
     */
    public function testDeletingSeriesRefreshesFormOptionsCache()
    {
        $series = Series::factory()->create(['name' => 'Doomed Series']);

        Cache::put('form-opts-series', [$series->id => 'Doomed Series'], 3600);

        $series->delete();

        $cached = Cache::get('form-opts-series');
        $this->assertNotNull($cached, 'form-opts-series cache should be re-warmed after deleting a series');
        $this->assertArrayNotHasKey($series->id, $cached);
    }
}
