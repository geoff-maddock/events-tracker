<?php

namespace App\Console\Commands;

use App\Models\Blog;
use App\Models\Entity;
use App\Models\Event;
use App\Models\Series;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\Visibility;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;
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
     *
     * A page belongs here only if it is reachable by a guest, self-canonical,
     * not noindexed, and not covered by a robots.txt Disallow rule. Notable
     * deliberate omissions:
     *  - /events/future — same listing as /events under a second URL
     *  - /tools, /radar, /job-status — redirect guests to /login
     *  - /search — Disallow'd in robots.txt
     *  - /activity, /all-modules — churn/internal nav, nothing to rank
     *
     *  - /events/today — now a permanent redirect to /events/tonight
     *
     * The three remaining time-window pages stay despite overlapping: each is
     * a distinct range with its own title targeting a distinct query.
     */
    protected const PAGES = [
        '/',
        '/events',
        '/events/tonight',
        '/events/this-weekend',
        '/events/this-week',
        '/events/week',
        '/events/past',
        '/entities',
        '/series',
        '/series/week',
        '/tags',
        '/blogs',
        '/threads',
        '/calendar',
        '/calendar/free',
        '/popular',
        '/about',
        '/help',
        '/privacy',
        '/tos',
    ];

    protected string $baseUrl;

    /**
     * Ids dropped by slugUrls(), keyed by section. Reported at the end so bad
     * slug data surfaces instead of silently shrinking the sitemap.
     *
     * @var array<string, array<int|string>>
     */
    protected array $skipped = [];

    public function handle(): int
    {
        $path = rtrim($this->option('path') ?: public_path(), '/');
        $this->baseUrl = rtrim(config('app.url'), '/');

        // All seven are generators, so building the map runs no queries — the
        // writability check below still happens before any real work.
        $sections = [
            'pages' => $this->pageUrls(),
            'events' => $this->eventUrls(),
            'entities' => $this->entityUrls(),
            'series' => $this->seriesUrls(),
            'tags' => $this->tagUrls(),
            'blogs' => $this->blogUrls(),
            'threads' => $this->threadUrls(),
        ];

        if ($problem = $this->writabilityProblem($path, array_keys($sections))) {
            $this->error($problem);

            return self::FAILURE;
        }

        $this->info('Generating sitemaps for '.$this->baseUrl.' into '.$path);

        $index = SitemapIndex::create();

        foreach ($sections as $name => $urls) {
            $written = $this->writeChunked($name, $urls, $path);

            if ($skipped = $this->skipped[$name] ?? []) {
                $this->warn(sprintf(
                    '  %s: skipped %d row(s) with an unusable slug (ids: %s%s)',
                    $name,
                    count($skipped),
                    implode(', ', array_slice($skipped, 0, 5)),
                    count($skipped) > 5 ? ', …' : ''
                ));
            }

            foreach ($written as [$file, $count, $lastmod]) {
                $entry = SitemapTag::create($this->baseUrl.'/'.$file);
                if ($lastmod) {
                    $entry->setLastModificationDate($lastmod);
                }
                $index->add($entry);
                $this->info(sprintf('  %s: %d urls', $file, $count));
            }
        }

        $index->writeToFile($path.'/sitemap.xml');
        $this->assertWritten($path.'/sitemap.xml');
        $this->info('Wrote sitemap index to '.$path.'/sitemap.xml');

        return self::SUCCESS;
    }

    /**
     * Spatie's writeToFile() is a bare file_put_contents(): on a permission
     * problem it only raises a warning, so the command would otherwise leave a
     * partial (or entirely stale) set of sitemaps behind and still exit 0.
     * Check the directory and any files we are about to overwrite up front —
     * before spending the query budget — and fail properly instead.
     *
     * Returns a human-readable problem description, or null if all is well.
     *
     * @param array<string> $sections
     */
    protected function writabilityProblem(string $path, array $sections): ?string
    {
        if (! is_dir($path)) {
            return 'Sitemap directory does not exist: '.$path;
        }

        $stale = array_filter(
            (array) glob($path.'/sitemap*.xml'),
            fn ($file) => ! is_writable($file)
        );

        if ($stale !== []) {
            return sprintf(
                'Existing sitemap files are not writable by %s: %s — see docs/deployment_notes.md',
                get_current_user(),
                implode(', ', array_map('basename', $stale))
            );
        }

        // Replacing a file needs write permission on the file itself; only
        // *creating* one needs it on the directory. So an unwritable directory
        // is fatal just when something we are about to write does not exist.
        if (! is_writable($path)) {
            $expected = array_map(fn ($name) => 'sitemap-'.$name.'.xml', $sections);
            $expected[] = 'sitemap.xml';

            $missing = array_values(array_filter(
                $expected,
                fn ($file) => ! file_exists($path.'/'.$file)
            ));

            if ($missing !== []) {
                return sprintf(
                    'Sitemap directory %s is not writable by %s and these files must be created: %s — see docs/deployment_notes.md',
                    $path,
                    get_current_user(),
                    implode(', ', $missing)
                );
            }

            $this->warn('Directory is not writable: existing sitemaps can be replaced, but additional chunk files could not be created if a section outgrows one file.');
        }

        return null;
    }

    /**
     * writeToFile() swallows failures, so confirm something actually landed on
     * disk. Covers what the up-front check cannot predict — a chunk file that
     * did not exist yet, a full disk, a read-only remount mid-run.
     */
    protected function assertWritten(string $file): void
    {
        clearstatcache(true, $file);

        if (! is_file($file) || filesize($file) === 0) {
            throw new RuntimeException('Failed to write '.$file.' — check permissions and free disk space.');
        }
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
            $this->assertWritten($path.'/'.$file);
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
     * Threads are id-routed: unlike Event and Entity, Thread has no
     * getRouteKeyName() override, so /threads/{slug} 404s and the canonical
     * URL is /threads/{id} — which is what the thread index links to.
     *
     * @return iterable<Url>
     */
    protected function threadUrls(): iterable
    {
        $query = Thread::query()
            ->where('visibility_id', Visibility::VISIBILITY_PUBLIC)
            ->select(['id', 'updated_at']);

        foreach ($this->idUrls($query, 'threads') as $url) {
            yield $url;
        }
    }

    /**
     * Yield canonical URLs for a model query routed by primary key.
     *
     * @param \Illuminate\Database\Eloquent\Builder<covariant Model> $query
     * @return iterable<Url>
     */
    protected function idUrls($query, string $prefix): iterable
    {
        $urls = [];

        $query->chunkById(1000, function ($models) use (&$urls, $prefix) {
            foreach ($models as $model) {
                $url = Url::create($this->baseUrl.'/'.$prefix.'/'.$model->getKey());
                $updatedAt = $model->getAttribute('updated_at');
                if ($updatedAt) {
                    $url->setLastModificationDate($updatedAt);
                }
                $urls[] = $url;
            }
        });

        return $urls;
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
                    $this->skipped[$prefix][] = $model->getKey();

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
