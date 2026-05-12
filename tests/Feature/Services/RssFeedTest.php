<?php

namespace Tests\Feature\Services;

use App\Models\Event;
use App\Services\RssFeed;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RssFeedTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_event_export_rss_contains_channel_metadata_and_item_per_event(): void
    {
        $events = new Collection([
            Event::factory()->create(['name' => 'Test Event ZZ-Alpha']),
            Event::factory()->create(['name' => 'Test Event ZZ-Beta']),
        ]);

        $xml = (new RssFeed())->getEventExportRSS($events);

        $this->assertStringContainsString('<rss', $xml);
        $this->assertStringContainsString('xmlns:atom="http://www.w3.org/2005/Atom"', $xml);
        $this->assertStringContainsString('<channel>', $xml);
        $this->assertStringContainsString('<atom:link', $xml);
        $this->assertStringContainsString('Test Event ZZ-Alpha', $xml);
        $this->assertStringContainsString('Test Event ZZ-Beta', $xml);
        $this->assertSame(2, substr_count($xml, '<item>'));
    }

    public function test_empty_collection_produces_channel_with_no_items(): void
    {
        $xml = (new RssFeed())->getEventExportRSS(new Collection());

        $this->assertStringContainsString('<channel>', $xml);
        $this->assertSame(0, substr_count($xml, '<item>'));
    }

    public function test_result_is_cached(): void
    {
        $events = new Collection([Event::factory()->create(['name' => 'cache-marker-zz'])]);

        $first = (new RssFeed())->getEventExportRSS($events);

        // Force a different event set; cached value should be returned unchanged.
        $different = new Collection([Event::factory()->create(['name' => 'different-zz'])]);
        $second = (new RssFeed())->getEventExportRSS($different);

        $this->assertSame($first, $second);
        $this->assertStringContainsString('cache-marker-zz', $second);
    }
}
