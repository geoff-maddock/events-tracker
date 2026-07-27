<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EventArchiveTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        Cache::forget('event-archive-months');
    }

    public function test_archive_lists_months_with_public_events(): void
    {
        Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => '2024-06-15 20:00:00',
        ]);

        $response = $this->get('/events/archive');

        $response->assertOk();
        $response->assertSee('2024');
        $response->assertSee('/events/by-date/2024/6', false);
        $response->assertSee('June');
    }

    public function test_archive_excludes_months_with_only_private_events(): void
    {
        Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PRIVATE,
            'start_at' => '1999-03-15 20:00:00',
        ]);

        $response = $this->get('/events/archive');

        $response->assertOk();
        $response->assertDontSee('/events/by-date/1999/3', false);
    }

    public function test_archive_is_indexable_with_clean_canonical(): void
    {
        $response = $this->get('/events/archive');

        $response->assertOk();
        $response->assertDontSee('noindex', false);
    }

    public function test_month_link_target_loads(): void
    {
        Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => '2024-06-15 20:00:00',
        ]);

        $this->get('/events/by-date/2024/6')->assertOk();
    }
}
