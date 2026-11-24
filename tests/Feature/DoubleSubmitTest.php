<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Event;
use App\Models\EventResponse;
use App\Models\ResponseType;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use App\Services\EntityWriter;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Two requests arriving together (a double click, a retry) leave one record:
 * one attending response per person and event, and a quick-added entity whose
 * slug was taken in between still gets a slug of its own.
 */
class DoubleSubmitTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
    }

    #[Test]
    public function the_database_refuses_the_same_response_twice(): void
    {
        $this->assertTrue(Schema::hasIndex('event_responses', ['event_id', 'user_id', 'response_type_id'], 'unique'));

        $event = Event::factory()->create();
        $row = ['event_id' => $event->id, 'user_id' => $this->user->id, 'response_type_id' => ResponseType::ATTENDING];
        DB::table('event_responses')->insert($row);

        $this->expectException(UniqueConstraintViolationException::class);
        DB::table('event_responses')->insert($row);
    }

    #[Test]
    public function attending_twice_through_the_api_stores_one_response_and_one_activity(): void
    {
        $event = Event::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        $this->actingAs($this->user, 'sanctum');

        $this->postJson("/api/events/{$event->id}/attend")->assertOk();
        $this->postJson("/api/events/{$event->id}/attend")->assertOk();

        $this->assertSame(1, EventResponse::where('event_id', $event->id)->where('user_id', $this->user->id)->count());
        $this->assertSame(1, DB::table('activities')->where('object_table', 'Event')->where('object_id', $event->id)->where('action_id', 6)->count());
    }

    #[Test]
    public function attending_twice_on_the_site_stores_one_response(): void
    {
        $event = Event::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC]);

        $this->actingAs($this->user)->post("/events/{$event->id}/attend");
        $this->actingAs($this->user)->post("/events/{$event->id}/attend");

        $this->assertSame(1, EventResponse::where('event_id', $event->id)->where('user_id', $this->user->id)->count());
    }

    #[Test]
    public function a_quick_added_entity_whose_slug_is_taken_meanwhile_gets_the_next_one(): void
    {
        // stands in for the other request: its row lands after this one's check and before its insert
        $taken = false;
        Entity::creating(function (Entity $entity) use (&$taken) {
            if (!$taken) {
                $taken = true;
                DB::table('entities')->insert([
                    'name' => 'Zzq Race Hall', 'slug' => $entity->slug, 'short' => 'x', 'description' => 'x',
                    'entity_type_id' => $entity->entity_type_id, 'entity_status_id' => $entity->entity_status_id,
                    'created_by' => $this->user->id,
                ]);
            }
        });

        $entity = app(EntityWriter::class)->quickCreate($this->user, 'Zzq Race Hall', 'Venue');

        $this->assertSame('zzq-race-hall-1', $entity->slug);
        $this->assertSame(2, DB::table('entities')->where('name', 'Zzq Race Hall')->count());
    }
}
