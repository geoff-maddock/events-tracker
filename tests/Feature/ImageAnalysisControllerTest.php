<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserStatus;
use App\Services\TempImageStore;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImageAnalysisControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();

        config()->set('ai.provider', 'anthropic');
        config()->set('ai.anthropic.api_key', 'test-key');
        config()->set('ai.anthropic.api_url', 'https://api.anthropic.com/v1/messages');
    }

    protected function tearDown(): void
    {
        foreach ((array) glob(storage_path('app/' . TempImageStore::TEMP_DIR . '/*')) as $file) {
            if (is_string($file) && is_file($file)) {
                @unlink($file);
            }
        }

        parent::tearDown();
    }

    private function activeUser(): User
    {
        /** @var User $user */
        $user = User::factory()->create([
            'email_verified_at' => Carbon::now(),
            'user_status_id' => UserStatus::ACTIVE,
        ]);

        return $user;
    }

    private function fakeAnalysis(string $json = '{"name":"Analyzed Event"}'): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => $json]],
            ], 200),
        ]);
    }

    private function image(): UploadedFile
    {
        return UploadedFile::fake()->image('flyer.jpg');
    }

    // ── Auth ─────────────────────────────────────────────────────────────

    public function test_a_guest_cannot_analyze(): void
    {
        $this->postJson('/images/analyze', ['image' => $this->image()])->assertStatus(401);
    }

    public function test_a_guest_cannot_stash(): void
    {
        $this->postJson('/images/stash', ['image' => $this->image()])->assertStatus(401);
    }

    /**
     * Entity create is gated on `auth` while event and series create are gated
     * on `verified`, so the shared endpoint must not require verification.
     */
    public function test_an_unverified_user_can_still_analyze(): void
    {
        $this->fakeAnalysis();

        $user = User::factory()->create([
            'email_verified_at' => null,
            'user_status_id' => UserStatus::ACTIVE,
        ]);

        $this->actingAs($user)
            ->postJson('/images/analyze', ['image' => $this->image(), 'context' => 'entity'])
            ->assertStatus(200);
    }

    // ── Validation ───────────────────────────────────────────────────────

    public function test_an_image_is_required(): void
    {
        $this->actingAs($this->activeUser())
            ->postJson('/images/analyze', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('image');
    }

    public function test_a_non_image_upload_is_rejected(): void
    {
        $this->actingAs($this->activeUser())
            ->postJson('/images/analyze', ['image' => UploadedFile::fake()->create('notes.pdf', 12)])
            ->assertStatus(422)
            ->assertJsonValidationErrors('image');
    }

    public function test_an_oversized_image_is_rejected(): void
    {
        $this->actingAs($this->activeUser())
            ->postJson('/images/stash', ['image' => UploadedFile::fake()->image('huge.jpg')->size(10241)])
            ->assertStatus(422)
            ->assertJsonValidationErrors('image');
    }

    public function test_an_unsupported_context_is_rejected(): void
    {
        $this->actingAs($this->activeUser())
            ->postJson('/images/analyze', ['image' => $this->image(), 'context' => 'blog'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('context');
    }

    // ── Stash ────────────────────────────────────────────────────────────

    public function test_stash_returns_a_token_and_writes_the_file_without_calling_the_api(): void
    {
        Http::fake();

        $response = $this->actingAs($this->activeUser())
            ->postJson('/images/stash', ['image' => $this->image()]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $token = $response->json('image_temp_token');
        $this->assertNotEmpty($token);
        $this->assertFileExists(storage_path('app/' . TempImageStore::TEMP_DIR . '/' . $token));

        // Stashing must never hit the paid API.
        Http::assertNothingSent();
    }

    // ── Analyze ──────────────────────────────────────────────────────────

    public function test_analyze_returns_extracted_data_and_a_token(): void
    {
        $this->fakeAnalysis();

        $response = $this->actingAs($this->activeUser())
            ->postJson('/images/analyze', ['image' => $this->image(), 'context' => 'event']);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'data' => ['name' => 'Analyzed Event']])
            ->assertJsonStructure(['success', 'data', 'image_temp_token', 'flyer_temp_token']);

        $this->assertFileExists(
            storage_path('app/' . TempImageStore::TEMP_DIR . '/' . $response->json('image_temp_token'))
        );
    }

    public function test_analyze_reuses_a_supplied_token_rather_than_stashing_twice(): void
    {
        $this->fakeAnalysis();
        $user = $this->activeUser();

        $stashed = $this->actingAs($user)
            ->postJson('/images/stash', ['image' => $this->image()])
            ->json('image_temp_token');

        $analyzed = $this->actingAs($user)
            ->postJson('/images/analyze', [
                'image' => $this->image(),
                'context' => 'event',
                'image_temp_token' => $stashed,
            ])
            ->json('image_temp_token');

        $this->assertSame($stashed, $analyzed);
        $this->assertCount(1, (array) glob(storage_path('app/' . TempImageStore::TEMP_DIR . '/*')));
    }

    public function test_an_analysis_failure_returns_422_with_the_message(): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response(['error' => 'overloaded'], 503)]);

        $this->actingAs($this->activeUser())
            ->postJson('/images/analyze', ['image' => $this->image()])
            ->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonPath('message', fn ($m) => str_contains((string) $m, 'could not be analysed'));
    }

    public function test_a_missing_api_key_returns_422(): void
    {
        config()->set('ai.anthropic.api_key', '');

        $this->actingAs($this->activeUser())
            ->postJson('/images/analyze', ['image' => $this->image()])
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    // ── Back-compat ──────────────────────────────────────────────────────

    public function test_the_deprecated_flyer_route_still_works_and_returns_the_legacy_token_field(): void
    {
        $this->fakeAnalysis();

        $response = $this->actingAs($this->activeUser())
            ->postJson('/events/analyze-flyer', ['image' => $this->image()]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $this->assertSame(
            $response->json('image_temp_token'),
            $response->json('flyer_temp_token'),
            'The legacy field must mirror the current one while the alias exists.'
        );
    }
}
