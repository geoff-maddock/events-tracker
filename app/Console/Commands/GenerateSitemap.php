<?php

namespace App\Console\Commands;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Series;
use Illuminate\Console\Command;
use Psr\Http\Message\UriInterface;
use Spatie\Sitemap\SitemapGenerator;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the sitemap.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->line('<fg=white;bg=black>Creating sitemap for: '.config('app.url').'</>');
        $this->line('<fg=white;bg=green>Output to '.public_path('sitemap.xml').'</>');
        // modify this to your own needs
        SitemapGenerator::create(config('app.url'))
            ->hasCrawled(function (Url $url) {
                if (strpos($url->segment(1), 'email') !== false) {
                    return;
                }

                // skip the redirect page
                if (strpos($url->segment(1), 'redirect') !== false) {
                    return;
                }

                // skip user tabs
                if (strpos($url->url, '?') !== false) {
                    return;
                }

                // skip edit links
                if (strpos($url->path(), '/edit') !== false) {
                    return;
                }

                // skip user links
                if (strpos($url->path(), '/users') !== false) {
                    return;
                }

                // skip follow links
                if (strpos($url->path(), '/follow') !== false) {
                    return;
                }

                // skip day_offset urls
                if (strpos($url->segment(1), '?day_offset') !== false) {
                    return;
                }

                // blacklist entities in the config
                if ($blacklist = config('app.spider_blacklist')) {
                    $blacklistArray = explode(',', $blacklist);
                    foreach ($blacklistArray as $item) {
                        if ($url->path() === '/entities/'.$item) {
                            return;
                        }
                    }
                }

                // if an event, get the event's updated at time and use
                if ($url->segment(1) === 'events' && is_numeric($url->segment(2))) {
                    $event = Event::find($url->segment(2));
                    $url->setLastModificationDate($event->updated_at);
                }

                // if a series, get the event's updated at time and use
                if ($url->segment(1) === 'series' && is_numeric($url->segment(2))) {
                    $series = Series::find($url->segment(2));
                    $url->setLastModificationDate($series->updated_at);
                }

                // if an entity, get the entities's updated at time and use
                if ($url->segment(1) === 'entities' && gettype($url->segment(2)) === 'string') {
                    $entity = Entity::where('slug', '=', $url->segment(2))->firstOrFail();
                    if ($entity !== null) {
                        $url->setLastModificationDate($entity->updated_at);
                    }
                }

                return $url;
            })
            ->shouldCrawl(function (UriInterface $url) {
                // Links present on the photos page won't be added to the
                // sitemap unless they are present on a crawlable page.
                if (strpos($url->getPath(), '/photos') !== false) {
                    return false;
                }

                // blacklist entities in the config
                if ($blacklist = config('app.spider_blacklist')) {
                    $blacklistArray = explode(',', $blacklist);
                    foreach ($blacklistArray as $item) {
                        if ($url->getPath() === '/entities/'.$item) {
                            return;
                        }
                    }
                }

                return strpos($url->getPath(), '/storage') === false;
            })
            ->maxTagsPerSitemap(10000)
            ->setMaximumCrawlCount(10000)
            ->writeToFile(public_path('sitemap.xml'));
    }
}
