<?php

namespace App\Console\Commands;

use App\Models\Blog;
use App\Models\Entity;
use App\Models\Event;
use App\Models\Series;
use App\Models\Tag;
use App\Models\Visibility;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\SitemapIndex;
use Spatie\Sitemap\Tags\Sitemap as SitemapTag;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate {--path= : Directory to write sitemap files into (defaults to public/)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a sitemap index and per-type sitemaps from the database';

    /**
     * Sitemap spec allows 50k URLs per file; leave headroom.
     */
    protected const MAX_URLS_PER_FILE = 45000;

    /**
     * Static landing pages worth indexing. Keep in sync with routes/web.php.
     */
    protected const PAGES = [
        '/',
        '/events',
        '/events/tonight',
        '/events/today',
        '/events/this-weekend',
        '/events/this-week',
        '/entities',
        '/series',
        '/tags',
        '/blogs',
        '/calendar',
    ];

    protected string $baseUrl;

    public function handle(): int
    {
        $path = rtrim($this->option('path') ?: public_path(), '/');
        $this->baseUrl = rtrim(config('app.url'), '/');

        $this->info('Generating sitemaps for '.$this->baseUrl.' into '.$path);

        $index = SitemapIndex::create();

        $sections = [
            'pages' => $this->pageUrls(),
            'events' => $this->eventUrls(),
            'entities' => $this->entityUrls(),
            'series' => $this->seriesUrls(),
            'tags' => $this->tagUrls(),
            'blogs' => $this->blogUrls(),
        ];

        foreach ($sections as $name => $urls) {
            foreach ($this->writeChunked($name, $urls, $path) as [$file, $count, $lastmod]) {
                $entry = SitemapTag::create($this->baseUrl.'/'.$file);
                if ($lastmod) {
                    $entry->setLastModificationDate($lastmod);
                }
                $index->add($entry);
                $this->info(sprintf('  %s: %d urls', $file, $count));
            }
        }

        $index->writeToFile($path.'/sitemap.xml');
        $this->info('Wrote sitemap index to '.$path.'/sitemap.xml');

        return self::SUCCESS;
    }

    /**
     * Write a section's URLs into one or more sitemap files, splitting at
     * MAX_URLS_PER_FILE. Returns [filename, url count, max lastmod] per file.
     *
     * @param iterable<Url> $urls
     * @return array<array{0: string, 1: int, 2: Carbon|null}>
     */
    protected function writeChunked(string $name, iterable $urls, string $path): array
    {
        $files = [];
        $sitemap = Sitemap::create();
        $count = 0;
        $chunk = 1;
        $lastmod = null;

        $flush = function () use (&$files, &$sitemap, &$count, &$chunk, &$lastmod, $name, $path) {
            if ($count === 0) {
                return;
            }
            $file = 'sitemap-'.$name.($chunk > 1 ? '-'.$chunk : '').'.xml';
            $sitemap->writeToFile($path.'/'.$file);
            $files[] = [$file, $count, $lastmod];
            $sitemap = Sitemap::create();
            $count = 0;
            $chunk++;
            $lastmod = null;
        };

        foreach ($urls as $url) {
            $sitemap->add($url);
            $count++;
            // lastModificationDate is a typed property left uninitialized on
            // URLs without a lastmod (e.g. static pages)
            if (isset($url->lastModificationDate) && (!$lastmod || $url->lastModificationDate > $lastmod)) {
                $lastmod = Carbon::instance($url->lastModificationDate);
            }
            if ($count >= self::MAX_URLS_PER_FILE) {
                $flush();
            }
        }
        $flush();

        return $files;
    }

    /**
     * @return iterable<Url>
     */
    protected function pageUrls(): iterable
    {
        foreach (self::PAGES as $page) {
            yield Url::create($this->baseUrl.$page);
        }

        // Scene hub pages are config-driven (config/scenes.php) rather than
        // a database table, so loop the config keys instead of hardcoding
        // slugs here — keeps this in sync with ScenesController/routes.
        yield Url::create($this->baseUrl.'/scenes');
        foreach (array_keys(config('scenes', [])) as $sceneSlug) {
            yield Url::create($this->baseUrl.'/scenes/'.$sceneSlug);
        }
    }

    /**
     * @return iterable<Url>
     */
    protected function eventUrls(): iterable
    {
        // deliberately NOT scopeVisible(null): that also matches proposal or
        // private events with a null creator — the sitemap wants public only
        $query = Event::query()
            ->where('visibility_id', Visibility::VISIBILITY_PUBLIC)
            ->select(['id', 'slug', 'updated_at']);

        foreach ($this->slugUrls($query, 'events') as $url) {
            yield $url;
        }
    }

    /**
     * @return iterable<Url>
     */
    protected function entityUrls(): iterable
    {
        $blacklist = array_filter(explode(',', (string) config('app.spider_blacklist')));

        $query = Entity::query()->active()->select(['id', 'slug', 'updated_at']);

        foreach ($this->slugUrls($query, 'entities') as $url) {
            foreach ($blacklist as $item) {
                if ($url->url === $this->baseUrl.'/entities/'.$item) {
                    continue 2;
                }
            }
            yield $url;
        }
    }

    /**
     * @return iterable<Url>
     */
    protected function seriesUrls(): iterable
    {
        $query = Series::query()
            ->where('visibility_id', Visibility::VISIBILITY_PUBLIC)
            ->select(['id', 'slug', 'updated_at']);

        foreach ($this->slugUrls($query, 'series') as $url) {
            yield $url;
        }
    }

    /**
     * @return iterable<Url>
     */
    protected function tagUrls(): iterable
    {
        $query = Tag::query()->hasContent()->select(['id', 'slug', 'updated_at']);

        foreach ($this->slugUrls($query, 'tags') as $url) {
            yield $url;
        }
    }

    /**
     * @return iterable<Url>
     */
    protected function blogUrls(): iterable
    {
        $query = Blog::query()
            ->where('visibility_id', Visibility::VISIBILITY_PUBLIC)
            ->select(['id', 'slug', 'updated_at']);

        foreach ($this->slugUrls($query, 'blogs') as $url) {
            yield $url;
        }
    }

    /**
     * Yield canonical slug URLs for a model query, chunked to keep memory flat.
     * Skips rows whose slug is empty, purely numeric (route binding treats
     * those as ids), or not a clean lowercase slug (junk data like URLs or
     * names with spaces stored as slugs).
     *
     * @param \Illuminate\Database\Eloquent\Builder<covariant Model> $query
     * @return iterable<Url>
     */
    protected function slugUrls($query, string $prefix): iterable
    {
        $urls = [];

        $query->chunkById(1000, function ($models) use (&$urls, $prefix) {
            foreach ($models as $model) {
                $slug = strtolower((string) $model->getAttribute('slug'));
                if ($slug === '' || ctype_digit($slug) || !preg_match('/^[a-z0-9-]+$/', $slug)) {
                    continue;
                }
                $url = Url::create($this->baseUrl.'/'.$prefix.'/'.$slug);
                $updatedAt = $model->getAttribute('updated_at');
                if ($updatedAt) {
                    $url->setLastModificationDate($updatedAt);
                }
                $urls[] = $url;
            }
        });

        return $urls;
    }
}
