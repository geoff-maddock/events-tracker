<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Forum;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * API content writes need a verified email, like the web (#2314). Uses real
 * credentials rather than actingAs(), so both ways auth.either signs a
 * caller in are covered.
 */
class ApiVerifiedEmailWriteTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function unverifiedUser(): User
    {
        return User::factory()->create(['user_status_id' => UserStatus::PENDING, 'email_verified_at' => null]);
    }

    private function verifiedUser(): User
    {
        return User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
    }

    /**
     * @return array<string, string>
     */
    private function tokenHeaders(User $user): array
    {
        return ['Accept' => 'application/json', 'Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken];
    }

    /**
     * @return array<string, string>
     */
    private function basicHeaders(User $user): array
    {
        return ['Accept' => 'application/json', 'Authorization' => 'Basic '.base64_encode($user->email.':password')];
    }

    /**
     * @return array<string, mixed>
     */
    private function threadPayload(): array
    {
        return [
            'name' => 'ZZ verified thread',
            'body' => 'Thread body',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'forum_id' => Forum::factory()->create()->id,
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function callers(): array
    {
        return ['sanctum token' => ['tokenHeaders'], 'basic auth' => ['basicHeaders']];
    }

    #[DataProvider('callers')]
    public function test_an_unverified_account_cannot_create_content(string $headers): void
    {
        $user = $this->unverifiedUser();

        $this->postJson('/api/threads', $this->threadPayload(), $this->{$headers}($user))
            ->assertForbidden()
            ->assertJson(['message' => 'Your email address is not verified.']);

        $this->assertDatabaseMissing('threads', ['name' => 'ZZ verified thread']);
    }

    public function test_an_api_caller_without_a_json_accept_header_gets_json_not_a_redirect(): void
    {
        $user = $this->unverifiedUser();

        $this->post('/api/threads', $this->threadPayload(), ['Authorization' => 'Basic '.base64_encode($user->email.':password')])
            ->assertForbidden()
            ->assertJson(['message' => 'Your email address is not verified.']);
    }

    #[DataProvider('callers')]
    public function test_a_verified_account_can_create_content(string $headers): void
    {
        $user = $this->verifiedUser();

        $this->postJson('/api/threads', $this->threadPayload(), $this->{$headers}($user))
            ->assertCreated();

        $this->assertDatabaseHas('threads', ['name' => 'ZZ verified thread', 'created_by' => $user->id]);
    }

    #[DataProvider('callers')]
    public function test_an_unverified_account_can_still_read_follow_and_attend(string $headers): void
    {
        $user = $this->unverifiedUser();
        $event = Event::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC]);

        $this->getJson('/api/events', $this->{$headers}($user))->assertOk();
        $this->postJson("/api/events/{$event->id}/attend", [], $this->{$headers}($user))->assertSuccessful();
        $this->assertTrue($event->fresh()->attendees()->whereKey($user->id)->exists());
    }

    public function test_an_unverified_account_can_still_follow_with_a_token(): void
    {
        $user = $this->unverifiedUser();
        $tag = Tag::factory()->create();

        $this->postJson("/api/tags/{$tag->slug}/follow", [], $this->tokenHeaders($user))->assertSuccessful();
    }
}
