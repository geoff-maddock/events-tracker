<?php

namespace Tests\Unit\Filters;

use App\Filters\UserFilters;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class UserFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function applyFilters(array $filters)
    {
        $request = Request::create('/', 'GET', $filters);
        $filter = new UserFilters($request);

        return $filter->apply(User::query());
    }

    public function test_email_filter_does_partial_match(): void
    {
        User::factory()->create(['email' => 'alice@example.com']);
        User::factory()->create(['email' => 'bob@other.com']);

        $results = $this->applyFilters(['email' => 'alice'])->get();

        $this->assertCount(1, $results);
    }

    public function test_name_filter_does_partial_match(): void
    {
        User::factory()->create(['name' => 'Alice']);
        User::factory()->create(['name' => 'Bob']);

        $results = $this->applyFilters(['name' => 'Alic'])->get();

        $this->assertCount(1, $results);
    }

    public function test_is_verified_true_returns_only_verified_users(): void
    {
        User::factory()->create(['email_verified_at' => now()]);
        User::factory()->create(['email_verified_at' => null]);

        $results = $this->applyFilters(['is_verified' => 'true'])->get();

        foreach ($results as $user) {
            $this->assertNotNull($user->email_verified_at);
        }
    }

    public function test_is_verified_false_returns_only_unverified_users(): void
    {
        User::factory()->create(['email_verified_at' => now()]);
        User::factory()->create(['email_verified_at' => null]);

        $results = $this->applyFilters(['is_verified' => 'false'])->get();

        foreach ($results as $user) {
            $this->assertNull($user->email_verified_at);
        }
    }
}
