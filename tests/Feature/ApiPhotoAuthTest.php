<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Series;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Photo uploads must require an authenticated owner (or admin). The web
 * routes (events/entities/series {id}/photos) were previously reachable
 * anonymously; the API routes were authenticated but had no ownership
 * check. The ownership guard runs before any file is processed/stored.
 */
class ApiPhotoAuthTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $owner;
    private User $attacker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        // CSRF is irrelevant to the auth/ownership guards under test; drop it
        // so the web POST routes reach the controller.
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->owner = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->attacker = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
    }

    private function image(): UploadedFile
    {
        return UploadedFile::fake()->image('photo.jpg');
    }

    private function jsonHeaders(): array
    {
        return ['Accept' => 'application/json'];
    }

    /** @test */
    public function api_event_add_photo_rejects_non_owner(): void
    {
        $event = Event::factory()->create(['created_by' => $this->owner->id]);

        $this->actingAs($this->attacker, 'sanctum');
        $this->post('/api/events/'.$event->id.'/photos', ['file' => $this->image()], $this->jsonHeaders())
            ->assertStatus(403);

        $this->assertSame(0, $event->photos()->count());
    }

    /** @test */
    public function api_entity_add_photo_rejects_non_owner(): void
    {
        $entity = Entity::factory()->create(['created_by' => $this->owner->id]);

        $this->actingAs($this->attacker, 'sanctum');
        $this->post('/api/entities/'.$entity->id.'/photos', ['file' => $this->image()], $this->jsonHeaders())
            ->assertStatus(403);

        $this->assertSame(0, $entity->photos()->count());
    }

    /** @test */
    public function api_series_add_photo_rejects_non_owner(): void
    {
        $series = Series::factory()->create(['created_by' => $this->owner->id]);

        $this->actingAs($this->attacker, 'sanctum');
        $this->post('/api/series/'.$series->id.'/photos', ['file' => $this->image()], $this->jsonHeaders())
            ->assertStatus(403);

        $this->assertSame(0, $series->photos()->count());
    }

    /** @test */
    public function web_event_add_photo_is_blocked_for_guest(): void
    {
        $event = Event::factory()->create(['created_by' => $this->owner->id]);

        $this->post('/events/'.$event->id.'/photos', ['file' => $this->image()])
            ->assertStatus(401);

        $this->assertSame(0, $event->photos()->count());
    }

    /** @test */
    public function web_event_add_photo_rejects_non_owner(): void
    {
        $event = Event::factory()->create(['created_by' => $this->owner->id]);

        $this->actingAs($this->attacker);
        $this->post('/events/'.$event->id.'/photos', ['file' => $this->image()])
            ->assertStatus(403);

        $this->assertSame(0, $event->photos()->count());
    }
}
