<?php

namespace Tests\Feature\Services;

use App\Models\Event;
use App\Models\Visibility;
use App\Services\RssFeed;
use Carbon\Carbon;
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

    private function upcoming(string $name, int $visibility = Visibility::VISIBILITY_PUBLIC): Event
    {
        return Event::factory()->create(['name' => $name, 'visibility_id' => $visibility, 'start_at' => Carbon::now()->addDays(3)]);
    }

    public function test_the_feed_has_channel_metadata_and_an_item_per_public_upcoming_event(): void
    {
        $this->upcoming('Test Event ZZ-Alpha');
        $this->upcoming('Test Event ZZ-Beta');
        $this->upcoming('Test Event ZZ-Private', Visibility::VISIBILITY_PRIVATE);
        Event::factory()->create(['name' => 'Test Event ZZ-Past', 'visibility_id' => Visibility::VISIBILITY_PUBLIC, 'start_at' => Carbon::now()->subDays(3)]);

        $xml = (new RssFeed())->getRSS();

        $this->assertStringContainsString('<rss', $xml);
        $this->assertStringContainsString('xmlns:atom="http://www.w3.org/2005/Atom"', $xml);
        $this->assertStringContainsString('<channel>', $xml);
        $this->assertStringContainsString('<atom:link', $xml);
        $this->assertStringContainsString('Test Event ZZ-Alpha', $xml);
        $this->assertStringContainsString('Test Event ZZ-Beta', $xml);
        $this->assertStringNotContainsString('ZZ-Private', $xml);
        $this->assertStringNotContainsString('ZZ-Past', $xml);
    }

    public function test_no_upcoming_events_produces_a_channel_with_no_items(): void
    {
        $xml = (new RssFeed())->getRSS();

        $this->assertStringContainsString('<channel>', $xml);
        $this->assertSame(0, substr_count($xml, '<item>'));
    }

    public function test_the_feed_is_cached(): void
    {
        $this->upcoming('cache-marker-zz');
        $first = (new RssFeed())->getRSS();

        // a new event doesn't appear until the cached feed expires
        $this->upcoming('different-zz');
        $second = (new RssFeed())->getRSS();

        $this->assertSame($first, $second);
        $this->assertStringNotContainsString('different-zz', $second);
    }
}
