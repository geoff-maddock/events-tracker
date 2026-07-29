<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserStatusDotTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /** @test */
    public function users_index_shows_colored_status_dot_for_user_with_status(): void
    {
        $this->withExceptionHandling();

        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $response = $this->actingAs($user)->get('/users?limit=1000');
        $response->assertStatus(200);

        $card = $this->extractCard($response->getContent(), $user->id);
        $this->assertStringContainsString('data-status-dot', $card);
        $this->assertStringContainsString('bg-green-500', $card);
        $this->assertStringContainsString('title="Active"', $card);
    }

    /** @test */
    public function status_dot_color_matches_user_status(): void
    {
        $this->withExceptionHandling();

        $pending = User::factory()->create(['user_status_id' => UserStatus::PENDING]);
        $banned = User::factory()->create(['user_status_id' => UserStatus::BANNED]);

        $response = $this->actingAs($pending)->get('/users?limit=1000');
        $response->assertStatus(200);
        $html = $response->getContent();

        $this->assertStringContainsString('bg-yellow-400', $this->extractCard($html, $pending->id));
        $this->assertStringContainsString('bg-red-500', $this->extractCard($html, $banned->id));
    }

    /** @test */
    public function users_index_shows_no_dot_for_unmapped_status(): void
    {
        $this->withExceptionHandling();

        // user_status_id has no FK constraint; an unmapped id exercises the
        // defensive default branch of the partial.
        $user = User::factory()->create(['user_status_id' => 999]);

        $response = $this->actingAs($user)->get('/users?limit=1000');
        $response->assertStatus(200);

        $card = $this->extractCard($response->getContent(), $user->id);
        $this->assertStringNotContainsString('data-status-dot', $card);
    }

    private function extractCard(string $html, int $userId): string
    {
        $matched = preg_match(
            '/<article[^>]*id="user-card-'.$userId.'".*?<\/article>/s',
            $html,
            $m
        );
        $this->assertSame(1, $matched, "Card for user {$userId} not found on index");

        return $m[0];
    }
}
