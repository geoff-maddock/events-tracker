<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Event;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Server-side hardening from #2166: validated token names, a fail-closed
 * reset secret, upload size caps, content-write throttling and no
 * impersonating an equal or higher account.
 */
class ServerHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function makeUser(?string $group = null): User
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE, 'email_verified_at' => now()]);
        if ($group) {
            $user->assignGroup($group);
        }

        return $user->fresh();
    }

    public function test_token_creation_requires_a_token_name(): void
    {
        // this route uses HTTP basic auth, so sign in on the default guard
        $this->actingAs($this->makeUser())->postJson('/api/tokens/create', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('token_name');

        $this->actingAs($this->makeUser())->postJson('/api/tokens/create', ['token_name' => 'my app'])
            ->assertOk()
            ->assertJsonStructure(['token']);
    }

    public function test_password_reset_secret_fails_closed_when_unset(): void
    {
        config(['app.password_reset_secret' => null]);

        // an empty configured secret must not match an empty-ish or any given value
        $this->postJson('/api/user/send-password-reset-email', ['email' => 'a@example.com', 'secret' => 'anything'])
            ->assertStatus(401);
    }

    public function test_password_reset_secret_must_match(): void
    {
        config(['app.password_reset_secret' => 'the-real-secret']);

        $this->postJson('/api/user/send-password-reset-email', ['email' => 'a@example.com', 'secret' => 'the-real-secreT'])
            ->assertStatus(401);
        $status = $this->postJson('/api/user/send-password-reset-email', ['email' => 'a@example.com', 'secret' => 'the-real-secret'])->status();
        $this->assertNotSame(401, $status);
    }

    public function test_photo_uploads_are_capped(): void
    {
        $user = $this->makeUser();
        $event = Event::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/events/'.$event->id.'/photos', ['file' => UploadedFile::fake()->image('big.jpg')->size(6000)])
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');
    }

    public function test_comment_posting_is_throttled(): void
    {
        $user = $this->makeUser();
        $entity = Entity::factory()->create();

        for ($i = 1; $i <= 10; $i++) {
            $this->actingAs($user)->post(route('entities.comments.store', $entity), ['message' => "comment number {$i}"])->assertRedirect();
        }

        $this->actingAs($user)->post(route('entities.comments.store', $entity), ['message' => 'one too many'])->assertStatus(429);
    }

    public function test_admin_cannot_impersonate_an_admin_or_super_admin(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get(route('user.impersonate', $this->makeUser('super_admin')))->assertForbidden();
        $this->actingAs($admin)->get(route('user.impersonate', $this->makeUser('admin')))->assertForbidden();
        $this->assertSame($admin->id, auth()->id());

        $member = $this->makeUser();
        $this->actingAs($admin)->get(route('user.impersonate', $member))->assertRedirect('/');
        $this->assertSame($member->id, auth()->id());
    }

    public function test_super_admin_can_impersonate_an_admin(): void
    {
        $super = $this->makeUser('super_admin');
        $super->assignGroup('admin');
        $target = $this->makeUser('admin');

        $this->actingAs($super->fresh())->get(route('user.impersonate', $target))->assertRedirect('/');
        $this->assertSame($target->id, auth()->id());
    }
}
