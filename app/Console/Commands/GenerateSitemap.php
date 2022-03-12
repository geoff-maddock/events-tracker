<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Psr\Http\Message\UriInterface;
use Spatie\Sitemap\SitemapGenerator;

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
            ->shouldCrawl(function (UriInterface $url) {
                // All pages will be crawled, except the contact page.
                // Links present on the contact page won't be added to the
                // sitemap unless they are present on a crawlable page.

                return strpos($url->getPath(), '/storage') === false;
            })
            ->maxTagsPerSitemap(2000)
            ->setMaximumCrawlCount(2000)
            ->writeToFile(public_path('sitemap.xml'));
    }
}
