<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\AuthUserResource;
use App\Http\Resources\EntityResource;
use App\Http\Resources\EventResource;
use App\Http\Resources\TagResource;
use App\Http\Resources\UserResource;
use App\Models\Entity;
use App\Models\Event;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Exercise the conditional / optional branches of the larger Resources
 * that the structural tests in ResourceSerializationTest don't touch:
 * popularity_score injection, whenLoaded relations, and the optional
 * nested model paths.
 */
class ResourceEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function request(): Request
    {
        return Request::create('/', 'GET');
    }

    public function test_event_resource_includes_popularity_score_when_present(): void
    {
        $event = Event::factory()->create();
        $event->popularity_score = 42;

        $data = (new EventResource($event))->toArray($this->request());

        $this->assertArrayHasKey('popularity_score', $data);
        $this->assertSame(42, $data['popularity_score']);
    }

    public function test_event_resource_omits_popularity_score_when_absent(): void
    {
        $event = Event::factory()->create();

        $data = (new EventResource($event))->toArray($this->request());

        $this->assertArrayNotHasKey('popularity_score', $data);
    }

    public function test_event_resource_renders_null_promoter_and_venue_safely(): void
    {
        $event = Event::factory()->create(['promoter_id' => null, 'venue_id' => null]);

        $data = (new EventResource($event))->toArray($this->request());

        $this->assertNull($data['promoter']);
        $this->assertNull($data['venue']);
    }

    public function test_entity_resource_includes_popularity_score_when_present(): void
    {
        $entity = Entity::factory()->create();
        $entity->popularity_score = 99;

        $data = (new EntityResource($entity))->toArray($this->request());

        $this->assertArrayHasKey('popularity_score', $data);
        $this->assertSame(99, $data['popularity_score']);
    }

    public function test_entity_resource_handles_null_status_and_type(): void
    {
        $entity = Entity::factory()->create(['entity_status_id' => null, 'entity_type_id' => null]);

        $data = (new EntityResource($entity))->toArray($this->request());

        $this->assertNull($data['entity_status']);
        $this->assertNull($data['entity_type']);
    }

    public function test_tag_resource_includes_popularity_score_when_present(): void
    {
        $tag = Tag::factory()->create();
        $tag->popularity_score = 7;

        $data = (new TagResource($tag))->toArray($this->request());

        $this->assertArrayHasKey('popularity_score', $data);
        $this->assertSame(7, $data['popularity_score']);
    }

    public function test_user_resource_exposes_followed_collections(): void
    {
        $user = User::factory()->create();

        $data = (new UserResource($user))->toArray($this->request());

        // All four followed_* keys present regardless of whether relations are loaded.
        $this->assertArrayHasKey('followed_tags', $data);
        $this->assertArrayHasKey('followed_entities', $data);
        $this->assertArrayHasKey('followed_series', $data);
        $this->assertArrayHasKey('followed_threads', $data);
    }

    public function test_auth_user_resource_exposes_full_profile_shape(): void
    {
        $user = User::factory()->create();

        $data = (new AuthUserResource($user))->toArray($this->request());

        $expectedKeys = [
            'id', 'name', 'email', 'status', 'email_verified_at', 'last_active',
            'created_at', 'updated_at', 'profile', 'followed_tags',
            'followed_entities', 'followed_series', 'followed_threads',
            'groups', 'permissions', 'photos',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $data, "Expected key '$key' in AuthUserResource output.");
        }
    }
}
