<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * Screen reader support in forms (#2411): a field is tied to its error and
 * help text, required fields say so, and a failed submit lists the problems.
 */
class FormAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_a_field_with_an_error_is_invalid_and_described_by_it(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.form-group name="title" label="Title" error="The title is required." help-text="Shown in lists" required>
                <x-ui.input type="text" name="title" id="title" />
            </x-ui.form-group>
            BLADE);

        $this->assertMatchesRegularExpression('/<input[^>]*id="title"/', $html);
        preg_match('/<input[^>]*>/', $html, $input);

        $this->assertStringContainsString('aria-invalid="true"', $input[0]);
        $this->assertStringContainsString('aria-describedby="title-error"', $input[0]);
        $this->assertStringContainsString('aria-required="true"', $input[0]);
        $this->assertStringContainsString('<p id="title-error"', $html);
        // the asterisk is decoration once the field says it is required
        $this->assertMatchesRegularExpression('/<span[^>]*aria-hidden="true"[^>]*>\*<\/span>/', $html);
    }

    public function test_a_field_without_an_error_is_described_by_its_help_text(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.form-group name="slug" label="Slug" help-text="Unique name">
                <x-ui.select name="slug" id="slug"><option>a</option></x-ui.select>
            </x-ui.form-group>
            BLADE);

        preg_match('/<select[^>]*>/', $html, $select);

        $this->assertStringContainsString('aria-describedby="slug-help"', $select[0]);
        $this->assertStringNotContainsString('aria-invalid', $select[0]);
        $this->assertStringNotContainsString('aria-required', $select[0]);
        $this->assertStringContainsString('<p id="slug-help"', $html);
    }

    public function test_a_control_outside_a_group_gets_no_state(): void
    {
        $html = Blade::render('<x-ui.textarea name="notes" id="notes" /><x-ui.checkbox name="ok" id="ok" />');

        $this->assertStringNotContainsString('aria-', $html);
    }

    public function test_a_group_can_name_a_control_whose_id_is_not_its_name(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.form-group name="password_confirmation" for="password-confirm" label="Confirm" error="No match">
                <x-ui.input type="text" name="password_confirmation" id="password-confirm" />
            </x-ui.form-group>
            BLADE);

        $this->assertStringContainsString('for="password-confirm"', $html);
        $this->assertStringContainsString('aria-describedby="password-confirm-error"', $html);
        $this->assertStringContainsString('<p id="password-confirm-error"', $html);
    }

    public function test_every_form_group_label_in_the_views_points_at_a_control(): void
    {
        $checked = 0;

        foreach (Finder::create()->files()->in(resource_path('views'))->name('*.blade.php') as $file) {
            preg_match_all('/<x-ui\.form-group\b(.*?)<\/x-ui\.form-group>/s', $file->getContents(), $groups);

            foreach ($groups[1] as $group) {
                // a literal name or for; computed ones (:name) are covered by the rendered forms
                if (! preg_match('/^\s+(?:[^>]*?\s)?for="([^"]+)"/s', $group, $target) && ! preg_match('/^\s+name="([^"]+)"/s', $group, $target)) {
                    continue;
                }

                $checked++;
                $this->assertStringContainsString('id="' . $target[1] . '"', $group, $file->getRelativePathname() . ': ' . $target[1]);
            }
        }

        $this->assertGreaterThan(150, $checked);
    }

    public function test_a_failed_submit_shows_one_summary_of_the_problems(): void
    {
        $html = $this->followingRedirects()->from('/login')->post('/login', ['email' => '', 'password' => ''])->getContent();

        $this->assertSame(1, preg_match_all('/id="form-error-summary"/', $html));
        $this->assertMatchesRegularExpression('/<div id="form-error-summary" role="alert" tabindex="-1"/', $html);
        $this->assertMatchesRegularExpression('/<li data-error-field="email">[^<]+<\/li>/', $html);
        $this->assertMatchesRegularExpression('/<li data-error-field="password">[^<]+<\/li>/', $html);
    }

    public function test_a_page_without_errors_has_no_summary(): void
    {
        $this->get('/login')->assertOk()->assertDontSee('form-error-summary');
    }

    public function test_the_event_form_ties_an_error_to_its_field(): void
    {
        $user = User::factory()->create(['user_status_id' => 2]);

        $html = $this->actingAs($user)->followingRedirects()->from('/events/create')->post('/events', [])->getContent();

        $this->assertStringContainsString('id="form-error-summary"', $html);
        $this->assertMatchesRegularExpression('/<input[^>]*aria-invalid="true"[^>]*aria-describedby="name-error"[^>]*id="name"/s', $html);
        $this->assertStringContainsString('<p id="name-error"', $html);
    }
}
