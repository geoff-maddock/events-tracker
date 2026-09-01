<?php

namespace Tests\Feature\Services;

use App\Services\ImageAnalysisService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImageAnalysisServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        config()->set('ai.provider', 'anthropic');
        config()->set('ai.anthropic.api_key', 'test-key');
        config()->set('ai.anthropic.api_url', 'https://api.anthropic.com/v1/messages');
    }

    private function flyer(): UploadedFile
    {
        return UploadedFile::fake()->image('flyer.jpg');
    }

    private function fakeText(string $text): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => $text]],
            ], 200),
        ]);
    }

    // ── Response handling (context-independent) ───────────────────────────

    public function test_returns_decoded_json_payload_on_success(): void
    {
        $this->fakeText('{"name":"Test Event","start_at":"2026-08-15 20:00"}');

        $result = (new ImageAnalysisService())->analyze($this->flyer());

        $this->assertSame('Test Event', $result['name']);
        $this->assertSame('2026-08-15 20:00', $result['start_at']);
    }

    public function test_strips_markdown_code_fences_from_model_response(): void
    {
        $this->fakeText("```json\n{\"name\":\"Fenced Event\"}\n```");

        $result = (new ImageAnalysisService())->analyze($this->flyer());

        $this->assertSame('Fenced Event', $result['name']);
    }

    public function test_skips_leading_thinking_block_and_reads_text_block(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['type' => 'thinking', 'thinking' => ''],
                    ['type' => 'text', 'text' => '{"name":"Thoughtful Event"}'],
                ],
            ], 200),
        ]);

        $result = (new ImageAnalysisService())->analyze($this->flyer());

        $this->assertSame('Thoughtful Event', $result['name']);
    }

    public function test_api_failure_throws_runtime_exception(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response(['error' => 'overloaded'], 503),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The image could not be analysed');

        (new ImageAnalysisService())->analyze($this->flyer());
    }

    public function test_unparseable_response_throws_runtime_exception(): void
    {
        $this->fakeText('this is not json');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('AI returned an unexpected response');

        (new ImageAnalysisService())->analyze($this->flyer());
    }

    public function test_missing_api_key_throws(): void
    {
        config()->set('ai.anthropic.api_key', '');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Anthropic API key is not configured');

        (new ImageAnalysisService())->analyze($this->flyer());
    }

    public function test_unknown_provider_throws(): void
    {
        config()->set('ai.provider', 'openai');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unsupported AI provider');

        (new ImageAnalysisService())->analyze($this->flyer());
    }

    // ── Context selection ────────────────────────────────────────────────

    /**
     * @return array<string, array{0: string}>
     */
    public static function contextProvider(): array
    {
        return [
            'event' => ['event'],
            'entity' => ['entity'],
            'series' => ['series'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('contextProvider')]
    public function test_sends_the_system_and_user_prompt_for_the_context(string $context): void
    {
        $this->fakeText('{"name":"Contextual"}');

        (new ImageAnalysisService())->analyze($this->flyer(), $context);

        Http::assertSent(function ($request) use ($context) {
            // The system prompt is prefixed with today's date by datedPrompt().
            return str_contains($request['system'], config("ai.prompts.{$context}.system"))
                && $request['messages'][0]['content'][1]['text'] === config("ai.prompts.{$context}.user");
        });
    }

    public function test_defaults_to_the_event_context(): void
    {
        $this->fakeText('{"name":"Defaulted"}');

        (new ImageAnalysisService())->analyze($this->flyer());

        Http::assertSent(fn ($request) => str_contains($request['system'], config('ai.prompts.event.system')));
    }

    public function test_unknown_context_throws_invalid_argument(): void
    {
        Http::fake();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported analysis context: blog');

        (new ImageAnalysisService())->analyze($this->flyer(), 'blog');
    }

    public function test_missing_prompt_config_throws_and_mentions_config_clear(): void
    {
        Http::fake();
        config()->set('ai.prompts.entity.system', null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('config:clear');

        (new ImageAnalysisService())->analyze($this->flyer(), 'entity');
    }

    // ── Dated system prompt ──────────────────────────────────────────────

    /**
     * The model has no clock. Without today's date it anchors "every second
     * Friday" to an arbitrary point in the year and can return a past date.
     */
    public function test_prefixes_the_system_prompt_with_todays_date(): void
    {
        $this->fakeText('{"name":"Dated"}');

        $this->travelTo(\Carbon\Carbon::parse('2026-09-01'), function () {
            (new ImageAnalysisService())->analyze($this->flyer(), 'series');

            Http::assertSent(function ($request) {
                return str_contains($request['system'], "Today's date is 2026-09-01")
                    && str_contains($request['system'], 'Tuesday')
                    && str_contains($request['system'], 'never return a date in the past');
            });
        });
    }

    public function test_the_dated_prefix_keeps_the_configured_prompt_intact(): void
    {
        $this->fakeText('{"name":"Dated"}');

        (new ImageAnalysisService())->analyze($this->flyer(), 'entity');

        Http::assertSent(fn ($request) => str_contains($request['system'], config('ai.prompts.entity.system')));
    }

    /**
     * Config is cached at deploy time, so a date or year baked into the prompt
     * config would freeze there. It must come from datedPrompt() instead.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('contextProvider')]
    public function test_prompt_config_contains_no_hardcoded_year(string $context): void
    {
        $prompts = config("ai.prompts.{$context}");

        // Not a blanket year check: the entity prompt legitimately names the
        // 1971-2037 started_at bounds. What must never appear is the *current*
        // year, which is what a date() call in this config would bake in.
        $this->assertStringNotContainsString(
            date('Y'),
            $prompts['system'] . ' ' . $prompts['user'],
            "ai.prompts.{$context} contains the current year; config is cached at deploy time so it would go stale."
        );
    }

    // ── Key allowlisting ─────────────────────────────────────────────────

    public function test_strips_keys_outside_the_context_allowlist(): void
    {
        $this->fakeText('{"name":"Kept","occurrence_type_name":"Weekly","injected":"dropped"}');

        $result = (new ImageAnalysisService())->analyze($this->flyer(), 'entity');

        $this->assertSame('Kept', $result['name']);
        $this->assertArrayNotHasKey('injected', $result);
        // occurrence_* belongs to the series contract, not the entity one
        $this->assertArrayNotHasKey('occurrence_type_name', $result);
    }

    public function test_keeps_context_specific_keys(): void
    {
        $this->fakeText('{"name":"Weekly Thing","occurrence_type_name":"Weekly","occurrence_day_name":"Thursday"}');

        $result = (new ImageAnalysisService())->analyze($this->flyer(), 'series');

        $this->assertSame('Weekly', $result['occurrence_type_name']);
        $this->assertSame('Thursday', $result['occurrence_day_name']);
    }

    // ── Prompt config integrity ──────────────────────────────────────────

    /**
     * Regression for the stray-comma bug: a "," instead of a "." between two
     * concatenated prompt strings silently appends a numerically-keyed element
     * instead of extending the prompt, dropping that text from the request.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('contextProvider')]
    public function test_prompt_config_has_no_numeric_keys(string $context): void
    {
        $prompts = config("ai.prompts.{$context}");

        $this->assertIsArray($prompts);
        $this->assertSame(
            [],
            array_filter(array_keys($prompts), 'is_int'),
            "ai.prompts.{$context} has a numeric key - check for a ',' that should be a '.'"
        );
        $this->assertNotEmpty($prompts['system']);
        $this->assertNotEmpty($prompts['user']);
        $this->assertNotEmpty($prompts['keys']);
    }

    public function test_event_system_prompt_includes_the_description_rewrite_instruction(): void
    {
        $this->assertStringContainsString(
            're-write the description',
            config('ai.prompts.event.system')
        );
    }

    public function test_event_prompt_only_offers_seeded_event_types(): void
    {
        $user = config('ai.prompts.event.user');

        foreach (['DJ Set', 'Art Show', 'Theater'] as $unseeded) {
            $this->assertStringNotContainsString($unseeded, $user);
        }

        $this->assertStringContainsString('House Show', $user);
    }
}
