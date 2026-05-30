<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Tag;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class SearchInterpretationTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Carbon::setTestNow(Carbon::parse('2026-05-27 12:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function testDateOnlyQueryRedirectsToFilteredEvents(): void
    {
        $expected = route('events.index', [
            'filters' => [
                'start_at' => [
                    'start' => '2026-05-27 00:00:00',
                    'end'   => '2026-05-27 23:59:59',
                ],
            ],
        ]);

        $this->get('/search?keyword=today')->assertRedirect($expected);
    }

    public function testExactEntityNameRedirectsToEntityPage(): void
    {
        Entity::factory()->create(['name' => 'The Smiling Moose', 'slug' => 'the-smiling-moose']);

        $this->get('/search?keyword=' . urlencode('The Smiling Moose'))
            ->assertRedirect(route('entities.show', 'the-smiling-moose'));
    }

    public function testExactTagNameRedirectsToTagPage(): void
    {
        Tag::factory()->create(['name' => 'Drum and Bass', 'slug' => Str::slug('Drum and Bass')]);

        $this->get('/search?keyword=' . urlencode('Drum and Bass'))
            ->assertRedirect(route('tags.show', 'drum-and-bass'));
    }

    public function testMixedDateQueryRendersSearchWithBanner(): void
    {
        $response = $this->get('/search?keyword=' . urlencode('techno this weekend'));

        $response->assertOk();
        $response->assertViewIs('pages.search');
        $response->assertSee('View events for');
    }

    public function testUnrecognizedQueryRendersSearchNormally(): void
    {
        $response = $this->get('/search?keyword=' . urlencode('zxcvb qwerty'));

        $response->assertOk();
        $response->assertViewIs('pages.search');
        $response->assertDontSee('View events for');
    }
}
