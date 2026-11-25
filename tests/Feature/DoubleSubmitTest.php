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
    public function a_quick_add_that_loses_the_race_for_its_slug_tries_again(): void
    {
        // stands in for the other request's row landing between this one's check and its insert
        $attempts = 0;
        Entity::creating(function () use (&$attempts) {
            if (1 === ++$attempts) {
                throw new UniqueConstraintViolationException('mysql', 'insert into `entities`', [], new \Exception('Duplicate entry'));
            }
        });

        $entity = app(EntityWriter::class)->quickCreate($this->user, 'Zzq Race Hall', 'Venue');

        $this->assertSame(2, $attempts);
        $this->assertSame('zzq-race-hall', $entity->slug);
        $this->assertSame(1, DB::table('entities')->where('name', 'Zzq Race Hall')->count());
    }

    #[Test]
    public function a_quick_add_that_keeps_failing_leaves_nothing_behind(): void
    {
        // a clash that is not about the slug: the retry must not pile up half-made entities
        Entity::created(function (Entity $entity) {
            DB::table('entity_role')->insert(['entity_id' => $entity->id, 'role_id' => \App\Models\Role::where('name', 'Venue')->value('id')]);
        });

        try {
            app(EntityWriter::class)->quickCreate($this->user, 'Zzq Stuck Hall', 'Venue');
            $this->fail('The clash was swallowed.');
        } catch (UniqueConstraintViolationException) {
            $this->assertSame(0, DB::table('entities')->where('name', 'Zzq Stuck Hall')->count());
        }
    }

    #[Test]
    public function a_quick_added_name_as_long_as_the_slug_column_still_gets_a_numbered_slug(): void
    {
        $name = str_repeat('zzqlong ', 31).'hall';

        $first = app(EntityWriter::class)->quickCreate($this->user, $name, 'Venue');
        $second = app(EntityWriter::class)->quickCreate($this->user, $name, 'Venue');

        $this->assertLessThanOrEqual(255, strlen($second->slug));
        $this->assertSame($first->slug.'-1', $second->slug);
    }
}
