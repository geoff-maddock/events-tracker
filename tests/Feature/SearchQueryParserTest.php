<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\Tag;
use App\Services\SearchQueryParser;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class SearchQueryParserTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected SearchQueryParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        // The slug maps are cached (array driver in tests persists across cases).
        Cache::flush();
        $this->parser = new SearchQueryParser();
        // Freeze to a Wednesday so "this weekend" is unambiguously in the future.
        Carbon::setTestNow(Carbon::parse('2026-05-27 12:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function testEmptyQueryReturnsEmptyResult(): void
    {
        $result = $this->parser->parse('   ');

        $this->assertSame('', $result['original']);
        $this->assertSame('', $result['residualText']);
        $this->assertNull($result['dateRange']);
        $this->assertNull($result['entity']);
        $this->assertNull($result['tag']);
    }

    public function testDetectsTodayAsSingleDayRange(): void
    {
        $result = $this->parser->parse('today');

        $this->assertNotNull($result['dateRange']);
        $this->assertSame('2026-05-27 00:00:00', $result['dateRange']['start']);
        $this->assertSame('2026-05-27 23:59:59', $result['dateRange']['end']);
        $this->assertSame('', $result['residualText']);
    }

    public function testDetectsTomorrow(): void
    {
        $result = $this->parser->parse('tomorrow');

        $this->assertSame('2026-05-28 00:00:00', $result['dateRange']['start']);
        $this->assertSame('2026-05-28 23:59:59', $result['dateRange']['end']);
    }

    public function testDetectsThisWeekendAsSaturdaySunday(): void
    {
        $result = $this->parser->parse('this weekend');

        $this->assertSame('2026-05-30 00:00:00', $result['dateRange']['start']);
        $this->assertSame('2026-05-31 23:59:59', $result['dateRange']['end']);
    }

    public function testDetectsNextMonth(): void
    {
        $result = $this->parser->parse('next month');

        $this->assertSame('2026-06-01 00:00:00', $result['dateRange']['start']);
        $this->assertSame('2026-06-30 23:59:59', $result['dateRange']['end']);
    }

    public function testDetectsIsoDate(): void
    {
        $result = $this->parser->parse('2026-12-25');

        $this->assertSame('2026-12-25 00:00:00', $result['dateRange']['start']);
        $this->assertSame('2026-12-25 23:59:59', $result['dateRange']['end']);
    }

    public function testDetectsMonthDayAndDefaultsToCurrentYear(): void
    {
        $result = $this->parser->parse('February 16th');

        $this->assertSame('2026-02-16 00:00:00', $result['dateRange']['start']);
        $this->assertSame('2026-02-16 23:59:59', $result['dateRange']['end']);
    }

    public function testDetectsMonthDayWithExplicitYear(): void
    {
        $result = $this->parser->parse('Feb 16, 2027');

        $this->assertSame('2027-02-16 00:00:00', $result['dateRange']['start']);
    }

    public function testInvalidMonthDayDoesNotMatch(): void
    {
        $result = $this->parser->parse('Feb 30');

        $this->assertNull($result['dateRange']);
    }

    public function testDateLeavesResidualText(): void
    {
        $result = $this->parser->parse('techno this weekend');

        $this->assertNotNull($result['dateRange']);
        $this->assertSame('techno', $result['residualText']);
        // Entity/tag detection is skipped when a date is present.
        $this->assertNull($result['entity']);
        $this->assertNull($result['tag']);
    }

    public function testDetectsExactEntity(): void
    {
        $entity = Entity::factory()->create(['name' => 'The Smiling Moose', 'slug' => 'the-smiling-moose']);

        $result = $this->parser->parse('The Smiling Moose');

        $this->assertNotNull($result['entity']);
        $this->assertSame('the-smiling-moose', $result['entity']['slug']);
        $this->assertSame('The Smiling Moose', $result['entity']['name']);
        $this->assertSame('', $result['residualText']);
        $this->assertNull($result['tag']);
    }

    public function testUnlistedEntityIsNotDetected(): void
    {
        Entity::factory()->create([
            'name'             => 'Hidden Venue',
            'slug'             => 'hidden-venue',
            'entity_status_id' => EntityStatus::UNLISTED,
        ]);

        $result = $this->parser->parse('Hidden Venue');

        $this->assertNull($result['entity']);
    }

    public function testDetectsExactTag(): void
    {
        Tag::factory()->create(['name' => 'Drum and Bass', 'slug' => Str::slug('Drum and Bass')]);

        $result = $this->parser->parse('Drum and Bass');

        $this->assertNotNull($result['tag']);
        $this->assertSame('drum-and-bass', $result['tag']['slug']);
        $this->assertSame('', $result['residualText']);
    }

    public function testEntityTakesPrecedenceOverTag(): void
    {
        Entity::factory()->create(['name' => 'Overlap', 'slug' => 'overlap']);
        Tag::factory()->create(['name' => 'Overlap', 'slug' => 'overlap']);

        $result = $this->parser->parse('Overlap');

        $this->assertNotNull($result['entity']);
        $this->assertNull($result['tag']);
    }

    public function testPartialEntityNameIsNotAnExactMatch(): void
    {
        Entity::factory()->create(['name' => 'Foo Bar', 'slug' => 'foo-bar']);

        $result = $this->parser->parse('Foo Bar live show');

        $this->assertNull($result['entity']);
        $this->assertSame('Foo Bar live show', $result['residualText']);
    }

    public function testUnrecognizedQueryPassesThrough(): void
    {
        $result = $this->parser->parse('zxcvb qwerty');

        $this->assertNull($result['dateRange']);
        $this->assertNull($result['entity']);
        $this->assertNull($result['tag']);
        $this->assertSame('zxcvb qwerty', $result['residualText']);
    }
}
