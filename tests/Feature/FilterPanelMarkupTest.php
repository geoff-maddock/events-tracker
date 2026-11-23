<?php

namespace Tests\Feature;

use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * The "Show Filters" toggle is one script, resources/assets/js/filter-panel.js
 * (#2410). A view with the toggle must carry the markup that script reads and
 * must not bring a handler of its own.
 */
class FilterPanelMarkupTest extends TestCase
{
    public function test_every_filter_toggle_uses_the_shared_script(): void
    {
        $checked = 0;

        foreach (Finder::create()->files()->in(resource_path('views'))->name('*.blade.php') as $file) {
            $source = $file->getContents();
            if (!str_contains($source, 'id="filters-toggle-btn"')) {
                continue;
            }
            $checked++;
            $view = $file->getRelativePathname();

            $this->assertStringContainsString('data-has-filter=', $source, "$view: the toggle needs data-has-filter");
            $this->assertStringContainsString('id="filter-panel"', $source, "$view: the filter form needs id=\"filter-panel\"");
            $this->assertStringNotContainsString("getElementById('filters-toggle-btn')", $source, "$view: has its own toggle handler");
        }

        $this->assertGreaterThan(10, $checked);
        $this->assertStringContainsString("import './filter-panel';", file_get_contents(resource_path('assets/js/app.js')));
    }
}
