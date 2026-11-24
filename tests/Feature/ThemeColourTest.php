<?php

namespace Tests\Feature;

use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * Text colours come from the theme (#2411): a fixed palette shade such as
 * text-green-500 reads well on one of the light and dark themes and badly on
 * the other. Use text-success, text-warning, text-destructive or
 * text-muted-foreground, or give the class a dark: sibling.
 */
class ThemeColourTest extends TestCase
{
    public function test_views_do_not_hardcode_a_text_colour_for_one_theme_only(): void
    {
        // white-on-fill buttons over a photo: the fill is the same in both themes
        $fixedFills = ['partials/photo-gallery-tw.blade.php'];

        $views = Finder::create()->files()->in(resource_path('views'))->name('*.blade.php')->notPath(['emails', 'vendor']);
        $found = [];

        foreach ($views as $file) {
            if (in_array($file->getRelativePathname(), $fixedFills, true)) {
                continue;
            }

            foreach (explode("\n", $file->getContents()) as $number => $line) {
                if (! str_contains($line, 'dark:') && preg_match('/(?<![\w-])(?:hover:)?text-(?:gray|slate|zinc|red|green|blue|yellow|amber|orange)-\d{2,3}\b/', $line, $match)) {
                    $found[] = $file->getRelativePathname() . ':' . ($number + 1) . ' ' . $match[0];
                }
            }
        }

        $this->assertSame([], $found);
    }

    public function test_the_theme_defines_the_text_tokens_for_both_themes(): void
    {
        $css = file_get_contents(resource_path('css/tailwind.css'));

        foreach (['--success-text', '--warning-text', '--destructive-text'] as $token) {
            $this->assertSame(2, substr_count($css, $token . ':'), $token);
        }
    }
}
