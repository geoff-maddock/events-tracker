<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Series;
use App\Models\Tag;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Heading order (#2411): one h1 that is only the page title, and no level
 * skipped on the way down to a card's heading.
 */
class HeadingOrderTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function pages(): array
    {
        return [
            'home' => ['/'],
            'events' => ['/events'],
            'events by tag' => ['/events/tag/zz-heading-tag'],
            'week' => ['/events/week'],
            'series' => ['/series'],
            'entities' => ['/entities'],
            'forum' => ['/threads'],
            'search' => ['/search?keyword=Zq'],
        ];
    }

    #[DataProvider('pages')]
    public function test_headings_go_down_one_level_at_a_time(string $url): void
    {
        // something for every list, today so the day views show it
        Event::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC, 'start_at' => now()->addHours(1), 'name' => 'Zq Heading Night']);
        Series::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC, 'name' => 'Zq Heading Series']);
        Entity::factory()->create(['name' => 'Zq Heading Entity']);
        Tag::factory()->create(['name' => 'Zz Heading Tag', 'slug' => 'zz-heading-tag']);

        $html = $this->get($url)->assertOk()->getContent();
        $html = preg_replace('/<script\b.*?<\/script>/s', '', $html);

        preg_match_all('/<h([1-6])\b[^>]*>(.*?)<\/h\1>/s', $html, $headings, PREG_SET_ORDER);

        $this->assertSame(1, count(array_filter($headings, fn ($heading) => $heading[1] === '1')), 'one h1');
        $this->assertSame('1', $headings[0][1], 'the h1 comes first');
        $this->assertStringNotContainsString('<a ', $headings[0][2], 'the h1 is only the title');

        $previous = 0;
        foreach ($headings as [, $level, $text]) {
            $this->assertLessThanOrEqual($previous + 1, (int) $level, 'h' . $level . ' after h' . $previous . ': ' . trim(strip_tags($text)));
            $previous = (int) $level;
        }
    }
}
