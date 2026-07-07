<?php

namespace Tests\Feature\Console;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FixNumericEventSlugsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /**
     * The slug mutator now blocks digit-leading slugs on Eloquent writes,
     * so legacy rows have to be seeded with a raw update.
     */
    private function eventWithRawSlug(string $slug): Event
    {
        $event = Event::factory()->create();
        DB::table('events')->where('id', $event->id)->update(['slug' => $slug]);

        return $event->fresh();
    }

    public function test_digit_leading_slug_gets_dash_prepended(): void
    {
        $event = $this->eventWithRawSlug('123-zz-legacy');

        $this->artisan('events:fix-numeric-slugs')->assertExitCode(0);

        $this->assertSame('-123-zz-legacy', $event->fresh()->slug);
    }

    public function test_fully_numeric_slug_gets_dash_prepended(): void
    {
        $event = $this->eventWithRawSlug('987654321');

        $this->artisan('events:fix-numeric-slugs')->assertExitCode(0);

        $this->assertSame('-987654321', $event->fresh()->slug);
    }

    public function test_dry_run_does_not_change_anything(): void
    {
        $event = $this->eventWithRawSlug('123-zz-legacy');

        $this->artisan('events:fix-numeric-slugs', ['--dry-run' => true])->assertExitCode(0);

        $this->assertSame('123-zz-legacy', $event->fresh()->slug);
    }

    public function test_collision_falls_back_to_id_suffixed_slug(): void
    {
        Event::factory()->create(['slug' => '-123-zz-legacy']);
        $event = $this->eventWithRawSlug('123-zz-legacy');

        $this->artisan('events:fix-numeric-slugs')->assertExitCode(0);

        $this->assertSame('-123-zz-legacy-'.$event->id, $event->fresh()->slug);
    }

    public function test_clean_slugs_are_untouched(): void
    {
        $event = Event::factory()->create(['slug' => 'zz-clean-slug-123']);

        $this->artisan('events:fix-numeric-slugs')->assertExitCode(0);

        $this->assertSame('zz-clean-slug-123', $event->fresh()->slug);
    }
}
