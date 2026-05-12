<?php

namespace Tests\Unit\Services;

use App\Services\StringHelper;
use PHPUnit\Framework\TestCase;

class StringHelperTest extends TestCase
{
    public function test_slug_to_name_converts_hyphenated_slug_to_title_case(): void
    {
        $this->assertSame('Indie Rock Showcase', (new StringHelper())->SlugToName('indie-rock-showcase'));
    }

    public function test_slug_to_name_handles_single_word(): void
    {
        $this->assertSame('Concerts', (new StringHelper())->SlugToName('concerts'));
    }

    public function test_slug_to_name_handles_already_capitalized_input(): void
    {
        $this->assertSame('Indie Rock', (new StringHelper())->SlugToName('Indie-Rock'));
    }
}
