<?php

namespace Tests\Feature;

use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * Page behaviour that belongs to one shared script stays there (#2410).
 */
class SharedPageScriptsTest extends TestCase
{
    public function test_no_view_wires_up_auto_submit_itself(): void
    {
        // App.init() in public/js/custom.js already submits the form of every .auto-submit field
        foreach (Finder::create()->files()->in(resource_path('views'))->name('*.blade.php') as $file) {
            $this->assertDoesNotMatchRegularExpression(
                '/querySelectorAll\(\s*[\'"]\.auto-submit[\'"]\s*\)|\$\(\s*[\'"]\.auto-submit[\'"]\s*\)/',
                $file->getContents(),
                $file->getRelativePathname().': has its own auto-submit handler'
            );
        }

        $this->assertStringContainsString("$('.auto-submit').autoSubmit();", file_get_contents(public_path('js/custom.js')));
    }

    public function test_the_layout_loads_every_script_it_names(): void
    {
        preg_match_all("/asset\('\/(js\/[^']+)'\)/", file_get_contents(resource_path('views/layouts/app-tw.blade.php')), $scripts);

        $this->assertNotEmpty($scripts[1]);

        foreach ($scripts[1] as $script) {
            $this->assertFileExists(public_path($script));
        }
    }
}
