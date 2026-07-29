<?php

namespace Tests\Feature\Console;

use App\Models\Event;
use App\Models\Tag;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleanupJunkTagsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_deletes_junk_tags_and_pivots(): void
    {
        $numeric = Tag::factory()->create(['name' => '483', 'slug' => '483']);
        $urlTag = Tag::factory()->create(['name' => 'www.parktildark.com', 'slug' => 'wwwparktildarkcom']);
        $real = Tag::factory()->create(['name' => 'Techno', 'slug' => 'techno']);

        $event = Event::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        $event->tags()->attach([$numeric->id, $real->id]);

        $this->artisan('tags:cleanup')->assertSuccessful();

        $this->assertDatabaseMissing('tags', ['id' => $numeric->id]);
        $this->assertDatabaseMissing('tags', ['id' => $urlTag->id]);
        $this->assertDatabaseMissing('event_tag', ['tag_id' => $numeric->id]);
        $this->assertDatabaseHas('tags', ['id' => $real->id]);
        $this->assertDatabaseHas('event_tag', ['tag_id' => $real->id]);
    }

    public function test_dry_run_deletes_nothing(): void
    {
        $numeric = Tag::factory()->create(['name' => '117', 'slug' => '117']);

        $this->artisan('tags:cleanup', ['--dry-run' => true])->assertSuccessful();

        $this->assertDatabaseHas('tags', ['id' => $numeric->id]);
    }

    public function test_reports_junk_event_types_without_deleting(): void
    {
        $typeId = \Illuminate\Support\Facades\DB::table('event_types')->insertGetId([
            'name' => 'liveafterdeathshow.com',
            'slug' => 'liveafterdeathshowcom',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('tags:cleanup')
            ->expectsOutputToContain('liveafterdeathshow.com')
            ->assertSuccessful();

        $this->assertDatabaseHas('event_types', ['id' => $typeId]);
    }
}
