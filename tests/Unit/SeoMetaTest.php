<?php

namespace Tests\Unit;

use App\Services\SeoMeta;
use Illuminate\Http\Request;
use Tests\TestCase;

class SeoMetaTest extends TestCase
{
    public function test_canonical_url_strips_query_and_uses_app_url(): void
    {
        $request = Request::create('https://some-other-host.example/events?sort=name&direction=desc');

        $this->assertSame(
            rtrim(config('app.url'), '/').'/events',
            SeoMeta::canonicalUrl($request)
        );
    }

    public function test_canonical_url_for_root_is_bare_app_url(): void
    {
        $request = Request::create('/');

        $this->assertSame(rtrim(config('app.url'), '/'), SeoMeta::canonicalUrl($request));
    }

    public function test_should_noindex_on_list_state_params(): void
    {
        foreach (['sort=name', 'direction=desc', 'limit=100', 'filters[tag]=x', 'keyword=x', 'tabs[events]=created', 'page=2'] as $query) {
            $this->assertTrue(
                SeoMeta::shouldNoindex(Request::create('/events?'.$query)),
                'Expected noindex for ?'.$query
            );
        }
    }

    public function test_should_not_noindex_without_list_state_params(): void
    {
        $this->assertFalse(SeoMeta::shouldNoindex(Request::create('/events')));
        $this->assertFalse(SeoMeta::shouldNoindex(Request::create('/events?utm_source=newsletter')));
    }
}
