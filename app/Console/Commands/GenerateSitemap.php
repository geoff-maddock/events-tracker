<?php

namespace App\Console\Commands;

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
                if (strpos($url->path(), '?tab') !== false) {
                    return;
                }

                // skip day_offset urls
                if (strpos($url->segment(1), '?day_offset') !== false) {
                    return;
                }

                return $url;
            })
            ->shouldCrawl(function (UriInterface $url) {
                // Links present on the photos page won't be added to the
                // sitemap unless they are present on a crawlable page.
                if (strpos($url->getPath(), '/photos') !== false) {
                    return false;
                }

                return strpos($url->getPath(), '/storage') === false;
            })
            ->maxTagsPerSitemap(10000)
            ->setMaximumCrawlCount(10000)
            ->writeToFile(public_path('sitemap.xml'));
    }
}
