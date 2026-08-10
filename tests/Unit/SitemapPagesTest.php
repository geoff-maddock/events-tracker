<?php

namespace Tests\Unit;

use App\Console\Commands\GenerateSitemap;
use Illuminate\Http\Request;
use ReflectionClass;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

/**
 * Guards the static page list in GenerateSitemap. Submitting a URL that 404s,
 * redirects guests to /login, or is Disallow'd in robots.txt is worse than
 * omitting it, and the list is hand-maintained — so pin the invariants rather
 * than trusting it to stay in sync with routes/web.php by hand.
 */
class SitemapPagesTest extends TestCase
{
    /**
     * @return array<string>
     */
    protected function pages(): array
    {
        /** @var array<string> $pages */
        $pages = (new ReflectionClass(GenerateSitemap::class))->getConstant('PAGES');

        return $pages;
    }

    public function test_every_sitemap_page_resolves_to_a_registered_get_route(): void
    {
        $this->assertNotEmpty($this->pages());

        foreach ($this->pages() as $page) {
            try {
                $route = $this->app['router']->getRoutes()->match(Request::create($page, 'GET'));
            } catch (NotFoundHttpException) {
                $this->fail('Sitemap page '.$page.' does not match any registered GET route.');
            }

            $this->assertNotContains(
                'auth',
                $route->gatherMiddleware(),
                'Sitemap page '.$page.' is auth-gated; guests would be redirected to /login.'
            );
        }
    }

    public function test_no_sitemap_page_is_disallowed_by_robots_txt(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));
        $this->assertIsString($robots);

        // Only the wildcard block applies to the crawlers we care about; the
        // MJ12bot block above it blocks everything and would match every page.
        $wildcard = substr($robots, (int) strpos($robots, 'User-agent: *'));

        preg_match_all('/^Disallow:\s*(\S+)/m', $wildcard, $matches);
        $rules = array_filter($matches[1], fn ($rule) => ! str_contains($rule, '='));

        foreach ($this->pages() as $page) {
            foreach ($rules as $rule) {
                $pattern = '#^'.str_replace('\*', '.*', preg_quote(rtrim($rule, '/'), '#')).'(/|$)#';

                $this->assertDoesNotMatchRegularExpression(
                    $pattern,
                    $page,
                    'Sitemap page '.$page.' is Disallow\'d in robots.txt by "'.$rule.'".'
                );
            }
        }
    }

    public function test_pages_are_unique_and_root_relative(): void
    {
        $pages = $this->pages();

        $this->assertSame(array_unique($pages), $pages, 'Duplicate entries in the sitemap page list.');

        foreach ($pages as $page) {
            $this->assertStringStartsWith('/', $page, $page.' must be root-relative.');
            $this->assertSame($page, rtrim($page, '/') ?: '/', $page.' must not have a trailing slash.');
        }
    }
}
