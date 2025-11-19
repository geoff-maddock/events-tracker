<?php

namespace App\Console\Commands;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Series;
use Illuminate\Console\Command;
use Psr\Http\Message\UriInterface;
use Spatie\Sitemap\Sitemap;
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
        $sitemap = SitemapGenerator::create(config('app.url'))
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

                // skip event add links
                if (strpos($url->path(), '/events/add') !== false) {
                    return;
                }               
                                    
                // skip create routes
                if (strpos($url->path(), '/create') !== false) {
                    return;
                }

                // skip edit links
                if (strpos($url->path(), '/edit') !== false) {
                    return;
                }

                // skip upcoming events links
                if (strpos($url->path(), '/events/upcoming') !== false) {
                    return;
                }

                // skip upcoming links
                if (strpos($url->path(), '/upcoming') !== false) {
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

                // skip day_offset urls
                if (strpos($url->segment(1), '?day_offset') !== false) {
                    return;
                }

                // skip some specific urls
                if (strpos($url->segment(2), 'export') !== false) {
                    return;
                }

                if (strpos($url->segment(2), 'ical') !== false) {
                    return;
                }

                if (strpos($url->segment(2), 'rpp-reset') !== false) {
                    return;
                }
            
                if (strpos($url->segment(2), 'alias') !== false) {
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

                // if an event, get the event's updated at time and use
                if ($url->segment(1) === 'events' && gettype($url->segment(2)) === 'string' && $url->segment(2) !== 'related-to' && $url->segment(2) !== 'upcoming' && $url->segment(2) !== 'tag' && $url->segment(2) != 'role' && $url->segment(2) != 'type') {
                    $slug = $url->segment(2);
                    $event = Event::where('slug', '=', $slug)->first();
                    if ($event) {
                        $url->setLastModificationDate($event->updated_at);
                    } else {
                        if ($url->url !== null) {
                            $this->line('<fg=yellow>Could not find event for URL: ' . $url->url . '</>');
                        }
                    }
                    return $url;
                }

                // if a series, get the event's updated at time and use
                if ($url->segment(1) === 'series' && gettype($url->segment(2)) === 'string' && $url->segment(2) !== 'tag' && $url->segment(2) != 'role') {
                    $slug = $url->segment(2);
                    $series = Series::where('slug', '=', $slug)->first();
                    if ($series) {
                        $url->setLastModificationDate($series->updated_at);
                    } else {
                        if ($url->url !== null) {
                            $this->line('<fg=yellow>Could not find series for URL: ' . $url->url . '</>');
                        }
                    }
                }

                // if an entity, get the entities's updated at time and use
                if ($url->segment(1) === 'entities' && gettype($url->segment(2)) === 'string' && $url->segment(2) !== 'tag' && $url->segment(2) != 'role') {
                    $slug = $url->segment(2);
                    $entity = Entity::where('slug', '=', $slug)->first();
                    if ($entity) {
                        $url->setLastModificationDate($entity->updated_at);
                    } else {
                        if ($url->url !== null) {
                            $this->line('<fg=yellow>Could not find entity for URL: ' . $url->url . '</>');
                        }
                    }
                }

                return $url;
            })
            ->shouldCrawl(function (UriInterface $url) {
                // Skip events/add links
                if (strpos($url->getPath(), '/events/add') !== false) {
                    return false;
                }

                // Skip events/upcoming links
                if (strpos($url->getPath(), '/events/upcoming') !== false) {
                    return false;
                }

                // Skip events by-date
                if (strpos($url->getPath(), '/events/by-date') !== false) {
                    return false;
                }

                // Links present on the photos page won't be added to the
                // sitemap unless they are present on a crawlable page.
                if (strpos($url->getPath(), '/photos') !== false) {
                    return false;
                }

                // blacklist entities in the config
                // if ($blacklist = config('app.spider_blacklist')) {
                //     $blacklistArray = explode(',', $blacklist);
                //     foreach ($blacklistArray as $item) {
                //         if ($url->getPath() === '/entities/'.$item) {
                //             return;
                //         }
                //     }
                // }

                return strpos($url->getPath(), '/storage') === false;
            })
            ->maxTagsPerSitemap(10000)
            ->setMaximumCrawlCount(10000)
            ->getSitemap();
        
        // Explicitly add important pages that might be missed by the crawler
        $sitemap->add(Url::create(config('app.url') . '/events')
            ->setPriority(0.9)
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY));
        
        // Write the sitemap to file
        $sitemap->writeToFile(public_path('sitemap.xml'));
    }
}
