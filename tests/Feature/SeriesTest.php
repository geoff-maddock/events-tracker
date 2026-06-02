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
}
