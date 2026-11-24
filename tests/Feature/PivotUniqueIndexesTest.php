<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Role;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use App\Services\EntityWriter;
use App\Services\EventWriter;
use App\Services\SeriesWriter;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The link tables hold each pair once (#2413), and the create paths, which
 * attach ids straight from the request, drop a repeated id instead of failing.
 */
class PivotUniqueIndexesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    #[Test]
    public function the_same_link_cannot_be_stored_twice(): void
    {
        $event = Event::factory()->create();
        $tag = Tag::factory()->create();
        $entity = Entity::factory()->create();

        $event->tags()->attach($tag->id);
        $event->entities()->attach($entity->id);

        foreach ([fn () => $event->tags()->attach($tag->id), fn () => $event->entities()->attach($entity->id)] as $again) {
            try {
                $again();
                $this->fail('A repeated link was stored.');
            } catch (UniqueConstraintViolationException) {
                // expected
            }
        }

        // sync and syncWithoutDetaching, which the update paths use, are unaffected
        $event->tags()->sync([$tag->id, $tag->id]);
        $event->entities()->syncWithoutDetaching([$entity->id]);

        $this->assertSame(1, DB::table('event_tag')->where('event_id', $event->id)->count());
        $this->assertSame(1, DB::table('entity_event')->where('event_id', $event->id)->count());
    }

    #[Test]
    public function creating_an_event_with_a_repeated_entity_links_it_once(): void
    {
        $user = User::factory()->create();
        $entity = Entity::factory()->create();
        $input = Event::factory()->make(['venue_id' => null, 'promoter_id' => null])->getAttributes();

        $event = app(EventWriter::class)->create($user, $input, [], [$entity->id, (string) $entity->id]);

        $this->assertSame(1, DB::table('entity_event')->where('event_id', $event->id)->count());
    }

    #[Test]
    public function creating_a_series_with_a_repeated_entity_links_it_once(): void
    {
        $user = User::factory()->create();
        $entity = Entity::factory()->create();
        $input = Series::factory()->make(['venue_id' => null, 'promoter_id' => null])->getAttributes();

        $series = app(SeriesWriter::class)->create($user, $input, [], [$entity->id, $entity->id]);

        $this->assertSame(1, DB::table('entity_series')->where('series_id', $series->id)->count());
    }

    #[Test]
    public function creating_an_entity_with_a_repeated_role_links_it_once(): void
    {
        $user = User::factory()->create();
        $role = Role::firstOrFail();
        $input = Entity::factory()->make()->getAttributes();

        $entity = app(EntityWriter::class)->create($user, $input, [], [], [$role->id, $role->id]);

        $this->assertSame(1, DB::table('entity_role')->where('entity_id', $entity->id)->count());
    }
}
