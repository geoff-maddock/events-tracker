<?php

namespace Tests\Feature\Console;

use App\Models\Event;
use App\Models\Series;
use App\Models\Tag;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerateSitemapCommandTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected string $outputPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outputPath = sys_get_temp_dir().'/sitemap-test-'.uniqid();
        mkdir($this->outputPath, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->outputPath.'/*.xml') ?: [] as $file) {
            unlink($file);
        }
        @rmdir($this->outputPath);

        parent::tearDown();
    }

    protected function generate(): string
    {
        $this->artisan('sitemap:generate', ['--path' => $this->outputPath])->assertSuccessful();

        $all = '';
        foreach (glob($this->outputPath.'/sitemap-*.xml') ?: [] as $file) {
            $all .= file_get_contents($file);
        }

        return $all;
    }

    public function test_generates_index_and_per_type_sitemaps(): void
    {
        $this->artisan('sitemap:generate', ['--path' => $this->outputPath])->assertSuccessful();

        $this->assertFileExists($this->outputPath.'/sitemap.xml');

        $index = simplexml_load_file($this->outputPath.'/sitemap.xml');
        $this->assertNotFalse($index);
        $this->assertSame('sitemapindex', $index->getName());

        $indexContent = (string) file_get_contents($this->outputPath.'/sitemap.xml');
        $this->assertStringContainsString('sitemap-pages.xml', $indexContent);

        foreach (glob($this->outputPath.'/sitemap-*.xml') ?: [] as $file) {
            $xml = simplexml_load_file($file);
            $this->assertNotFalse($xml, $file.' is not valid XML');
            $this->assertSame('urlset', $xml->getName());
        }
    }

    public function test_includes_the_time_window_landing_pages(): void
    {
        $content = $this->generate();

        $base = rtrim(config('app.url'), '/');
        $this->assertStringContainsString($base.'/events/tonight', $content);
        $this->assertStringContainsString($base.'/events/today', $content);
        $this->assertStringContainsString($base.'/events/this-weekend', $content);
        $this->assertStringContainsString($base.'/events/this-week', $content);
    }

    public function test_includes_public_content_and_excludes_non_public(): void
    {
        $public = Event::factory()->create([
            'slug' => 'sitemap-public-event',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);
        Event::factory()->create([
            'slug' => 'sitemap-private-event',
            'visibility_id' => Visibility::VISIBILITY_PRIVATE,
        ]);
        Series::factory()->create([
            'slug' => 'sitemap-cancelled-series',
            'visibility_id' => Visibility::VISIBILITY_CANCELLED,
        ]);

        $tagWithContent = Tag::factory()->create(['name' => 'Sitemap Tag', 'slug' => 'sitemap-tag']);
        $public->tags()->attach($tagWithContent->id);
        Tag::factory()->create(['name' => 'Sitemap Orphan', 'slug' => 'sitemap-orphan-tag']);

        $content = $this->generate();

        $base = rtrim(config('app.url'), '/');
        $this->assertStringContainsString($base.'/events/sitemap-public-event', $content);
        $this->assertStringNotContainsString('sitemap-private-event', $content);
        $this->assertStringNotContainsString('sitemap-cancelled-series', $content);
        $this->assertStringContainsString($base.'/tags/sitemap-tag', $content);
        $this->assertStringNotContainsString('sitemap-orphan-tag', $content);
    }

    public function test_skips_junk_and_numeric_slugs(): void
    {
        Event::factory()->create([
            'slug' => 'www.junk-url.com',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        $content = $this->generate();

        $this->assertStringNotContainsString('www.junk-url.com', $content);
    }

    public function test_no_url_matches_exclusion_patterns(): void
    {
        Event::factory()->create([
            'slug' => 'sitemap-clean-event',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        $content = $this->generate();

        preg_match_all('#<loc>(.*?)</loc>#', $content, $matches);
        $this->assertNotEmpty($matches[1]);

        $base = preg_quote(rtrim(config('app.url'), '/'), '#');
        $forbidden = '#(\?|%20|/go/|/search|/users|/events/add|/grid|/rpp-reset|/reset$|/alias|/export|/ical|/feed|/create|/edit|/login|/register|/password)#';

        foreach ($matches[1] as $loc) {
            $this->assertMatchesRegularExpression('#^'.$base.'#', $loc, 'URL not on canonical host: '.$loc);
            $this->assertDoesNotMatchRegularExpression($forbidden, $loc, 'Excluded pattern in sitemap: '.$loc);
            $path = parse_url($loc, PHP_URL_PATH) ?? '';
            $this->assertSame(strtolower($path), $path, 'Non-lowercase URL in sitemap: '.$loc);
        }
    }

    public function test_lastmod_uses_updated_at(): void
    {
        $event = Event::factory()->create([
            'slug' => 'sitemap-lastmod-event',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        $content = $this->generate();

        $this->assertMatchesRegularExpression(
            '#<loc>[^<]*/events/sitemap-lastmod-event</loc>\s*<lastmod>'.$event->updated_at->format('Y-m-d').'#s',
            $content
        );
    }
}
