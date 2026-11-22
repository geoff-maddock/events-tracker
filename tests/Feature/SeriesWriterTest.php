<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\EventType;
use App\Models\Group;
use App\Models\OccurrenceType;
use App\Models\Series;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The web form and the API write a series through the same service (#2407),
 * so what a write records does not depend on which one was used.
 */
class SeriesWriterTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $owner;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->owner = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        // an admin, so the write is by someone other than the owner
        $this->editor = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->editor->groups()->attach(Group::where('name', 'admin')->firstOrFail()->id);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'ZZ Writer Series',
            'slug' => 'zz-writer-series',
            'short' => 'A short blurb.',
            'event_type_id' => EventType::first()->id,
            'occurrence_type_id' => OccurrenceType::first()->id,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ], $overrides);
    }

    private function series(): Series
    {
        return Series::factory()->create(['slug' => 'zz-writer-series', 'created_by' => $this->owner->id, 'updated_by' => null]);
    }

    /** @return array<string, array{string}> */
    public static function writes(): array
    {
        return ['web form' => ['web'], 'API put' => ['put'], 'API patch' => ['patch']];
    }

    #[DataProvider('writes')]
    public function test_an_update_records_who_made_it_and_keeps_the_owner(string $via): void
    {
        $series = $this->series();
        $logged = Activity::count();

        match ($via) {
            'web' => $this->actingAs($this->editor)->put('/series/'.$series->slug, $this->payload(['name' => 'ZZ Renamed']))->assertSessionHasNoErrors(),
            'put' => $this->actingAs($this->editor, 'sanctum')->putJson('/api/series/'.$series->slug, $this->payload(['name' => 'ZZ Renamed']))->assertOk(),
            'patch' => $this->actingAs($this->editor, 'sanctum')->patchJson('/api/series/'.$series->slug, ['name' => 'ZZ Renamed'])->assertOk(),
        };

        $series->refresh();
        $this->assertSame('ZZ Renamed', $series->name);
        $this->assertSame($this->editor->id, $series->updated_by, 'who last changed it');
        $this->assertSame($this->owner->id, $series->created_by, 'the owner is unchanged');
        $this->assertSame($logged + 1, Activity::count(), 'one activity row per write');
    }

    public function test_a_new_series_belongs_to_its_creator_on_both_paths(): void
    {
        $member = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        // a member cannot name another owner on either path
        $this->actingAs($member)->post('/series', $this->payload(['slug' => 'zz-web-new', 'created_by' => $this->owner->id]));
        $this->actingAs($member, 'sanctum')->postJson('/api/series', $this->payload(['slug' => 'zz-api-new', 'created_by' => $this->owner->id]))->assertOk();

        foreach (['zz-web-new', 'zz-api-new'] as $slug) {
            $series = Series::where('slug', $slug)->firstOrFail();
            $this->assertSame($member->id, $series->created_by, $slug);
            $this->assertSame($member->id, $series->updated_by, $slug);
        }
    }
}
