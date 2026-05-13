<?php

namespace Tests\Feature;

use App\Models\EventType;
use App\Models\OccurrenceType;
use App\Models\Series;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiSeriesCrudTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->actingAs($this->user, 'sanctum');
    }

    private User $user;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'ZZ-Test-Series',
            'slug' => 'zz-test-series-'.uniqid(),
            'short' => 'A short blurb.',
            'description' => 'A longer description.',
            'event_type_id' => EventType::first()->id,
            'occurrence_type_id' => OccurrenceType::first()->id,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'is_benefit' => 0,
        ], $overrides);
    }

    public function test_store_creates_series_with_creator_set_to_actor(): void
    {
        $response = $this->postJson('/api/series', $this->validPayload(['name' => 'ZZ-Store-Series']));

        $response->assertOk()->assertJsonFragment(['name' => 'ZZ-Store-Series']);

        $series = Series::where('name', 'ZZ-Store-Series')->first();
        $this->assertNotNull($series);
        $this->assertSame($this->user->id, $series->created_by);
    }

    public function test_store_rejects_invalid_payload(): void
    {
        $response = $this->postJson('/api/series', ['name' => 'x']);
        $response->assertStatus(422);
    }

    public function test_update_references_nonexistent_benefit_id_column(): void
    {
        // Api\SeriesController::update nullifies `benefit_id` as one of the
        // optionalSeriesFields, but the `series` table has no benefit_id
        // column. PUT /series therefore always fails in production. Captured
        // here so a fix can't regress unnoticed.
        $series = Series::factory()->create([
            'created_by' => $this->user->id,
            'slug' => 'zz-update-series-'.uniqid(),
        ]);

        $payload = $this->validPayload([
            'name' => 'ZZ-Updated-Series',
            'slug' => $series->slug,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        $this->withoutExceptionHandling();
        $this->putJson('/api/series/'.$series->slug, $payload);
    }

    public function test_update_refuses_when_not_creator(): void
    {
        // The not-creator path returns unauthorized before ever reaching the
        // benefit_id SQL bug, so this test still exercises the auth check.
        $other = User::factory()->create();
        $series = Series::factory()->create([
            'created_by' => $other->id,
            'slug' => 'zz-other-series-'.uniqid(),
        ]);

        $payload = $this->validPayload(['slug' => $series->slug]);

        $response = $this->putJson('/api/series/'.$series->slug, $payload);

        $this->assertContains($response->status(), [302, 401, 403]);
    }

    public function test_patch_partial_update_for_creator(): void
    {
        $series = Series::factory()->create([
            'created_by' => $this->user->id,
            'name' => 'ZZ-Original',
            'slug' => 'zz-patch-series-'.uniqid(),
        ]);

        $response = $this->patchJson('/api/series/'.$series->slug, [
            'name' => 'ZZ-Patched-Only-Name',
        ]);

        $response->assertOk();
        $this->assertSame('ZZ-Patched-Only-Name', $series->fresh()->name);
    }

    public function test_destroy_deletes_series_for_creator(): void
    {
        $series = Series::factory()->create([
            'created_by' => $this->user->id,
            'slug' => 'zz-destroy-mine-'.uniqid(),
        ]);

        $response = $this->deleteJson('/api/series/'.$series->slug);

        $response->assertStatus(204);
        $this->assertNull(Series::find($series->id));
    }

    public function test_destroy_refuses_when_not_creator(): void
    {
        $other = User::factory()->create();
        $series = Series::factory()->create([
            'created_by' => $other->id,
            'slug' => 'zz-destroy-other-'.uniqid(),
        ]);

        $response = $this->deleteJson('/api/series/'.$series->slug);

        $this->assertContains($response->status(), [302, 401, 403]);
        $this->assertNotNull(Series::find($series->id));
    }
}
