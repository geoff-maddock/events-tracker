# User Status Dot Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Show a small colored status dot on each user card's avatar on the users index, visible to all viewers.

**Architecture:** A new Blade partial `users/status-dot.blade.php` maps `user_status_id` to a literal Tailwind color class and renders an absolutely-positioned dot; `users/card-tw.blade.php` includes it inside the avatar wrapper (made `relative`) and drops its dead status-badge block. No controller changes — `UsersController@index` already eager-loads `status`.

**Tech Stack:** Laravel 12 Blade, Tailwind 4 (classes must appear literally in Blade files — Tailwind only scans `resources/views` and JS), PHPUnit feature tests against MySQL.

## Global Constraints

- Color map (from spec): Active(2)=`bg-green-500`, Pending(1)=`bg-yellow-400`, Suspended(3)=`bg-orange-500`, Banned(4)=`bg-red-500`, Deleted(5)=`bg-gray-400`, null/unknown = no dot.
- Use full-form `@php ... @endphp` — the `@php(...)` paren form fails to compile in this app.
- Status constants come from `App\Models\UserStatus` (PENDING=1, ACTIVE=2, SUSPENDED=3, BANNED=4, DELETED=5).
- Feature tests: `use RefreshDatabase;` + `protected $seed = true;` + `$this->withExceptionHandling();` (suite convention).
- `User::factory()` assigns a RANDOM `user_status_id` — always pass an explicit `user_status_id` when the test depends on status.
- New Tailwind classes require `npm run build` before they're visible on a deployed/dev site.

---

### Task 1: Status dot partial + card integration (TDD)

**Files:**
- Create: `resources/views/users/status-dot.blade.php`
- Modify: `resources/views/users/card-tw.blade.php:4-19` (avatar wrapper)
- Test: `tests/Feature/UserStatusDotTest.php` (new)

**Interfaces:**
- Consumes: `$user` (an `App\Models\User` with `user_status_id` and eager-loaded `status`), `UserStatus` constants.
- Produces: partial `users/status-dot` — expects `$user` in scope; renders `<span data-status-dot ...>` or nothing. `data-status-dot` is the stable hook tests and future JS may rely on.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/UserStatusDotTest.php`:

```php
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

        $response = $this->get('/users?limit=1000');
        $response->assertStatus(200);

        $card = $this->extractCard($response->getContent(), $user->id);
        $this->assertStringContainsString('data-status-dot', $card);
        $this->assertStringContainsString('bg-green-500', $card);
        $this->assertStringContainsString('title="Active"', $card);
    }

    /** @test */
    public function users_index_shows_no_dot_for_user_without_status(): void
    {
        $this->withExceptionHandling();

        $user = User::factory()->create(['user_status_id' => null]);

        $response = $this->get('/users?limit=1000');
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./vendor/bin/phpunit tests/Feature/UserStatusDotTest.php`
Expected: FAIL — `data-status-dot` not found (feature not implemented). If the card itself isn't found, fix the test before proceeding, not the view.

- [ ] **Step 3: Create the partial**

Create `resources/views/users/status-dot.blade.php`:

```blade
{{-- Colored status indicator dot; expects $user in scope. Renders nothing without a mapped status. --}}
@php
$statusDotClass = match ($user->user_status_id) {
    \App\Models\UserStatus::ACTIVE => 'bg-green-500',
    \App\Models\UserStatus::PENDING => 'bg-yellow-400',
    \App\Models\UserStatus::SUSPENDED => 'bg-orange-500',
    \App\Models\UserStatus::BANNED => 'bg-red-500',
    \App\Models\UserStatus::DELETED => 'bg-gray-400',
    default => null,
};
@endphp
@if ($statusDotClass)
<span data-status-dot
    class="absolute bottom-1 right-1 w-4 h-4 rounded-full border-2 border-card {{ $statusDotClass }}"
    title="{{ optional($user->status)->name }}"></span>
@endif
```

- [ ] **Step 4: Wire it into the card**

In `resources/views/users/card-tw.blade.php`, change the avatar wrapper (line 5) from `<div class="mb-4">` to `<div class="mb-4 relative">` and add the include just before that div closes:

```blade
        <!-- User Avatar -->
        <div class="mb-4 relative">
            @if ($photo = $user->getPrimaryPhoto())
            ...existing avatar markup unchanged...
            @endif
            @include('users.status-dot')
        </div>
```

- [ ] **Step 5: Run test to verify it passes**

Run: `./vendor/bin/phpunit tests/Feature/UserStatusDotTest.php`
Expected: PASS (2 tests)

- [ ] **Step 6: Commit**

```bash
git add tests/Feature/UserStatusDotTest.php resources/views/users/status-dot.blade.php resources/views/users/card-tw.blade.php
git commit -m "Add colored status dot to user cards on users index"
```

---

### Task 2: Remove dead status badge block from card

**Files:**
- Modify: `resources/views/users/card-tw.blade.php:26-33`

**Interfaces:**
- Consumes: nothing new.
- Produces: nothing — pure dead-code removal. The block checks `$user->user_status`, a property that does not exist (the relation is `status`), so it has never rendered.

- [ ] **Step 1: Remove the block**

Delete these lines from `card-tw.blade.php`:

```blade
        <!-- User Status Badge -->
        @if (isset($user->user_status))
        <div class="mb-3">
            <span class="badge-tw {{ $user->user_status->name == 'Active' ? 'badge-primary-tw' : 'badge-secondary-tw' }} text-xs">
                {{ $user->user_status->name }}
            </span>
        </div>
        @endif
```

- [ ] **Step 2: Verify nothing else references the pattern and tests still pass**

Run: `grep -rn "user_status\b" resources/views/users/` — expect no hits in `card-tw.blade.php` (the `user_status_id` field in `form-tw.blade.php` is unrelated and stays).
Run: `./vendor/bin/phpunit tests/Feature/UserStatusDotTest.php`
Expected: PASS

- [ ] **Step 3: Commit**

```bash
git add resources/views/users/card-tw.blade.php
git commit -m "Remove dead user_status badge block from user card"
```

---

### Task 3: Build assets + full verification

**Files:**
- Modify: none (build output only)

**Interfaces:**
- Consumes: the literal classes from Task 1 (`bg-green-500`, `bg-yellow-400`, `bg-orange-500`, `bg-red-500`, `bg-gray-400`).
- Produces: compiled CSS containing those classes, so the dot is actually colored in the browser.

- [ ] **Step 1: Build and confirm classes survived the Tailwind scan**

Run: `npm run build && grep -c "bg-yellow-400" public/build/assets/*.css`
Expected: build succeeds; grep count ≥ 1.

- [ ] **Step 2: Run static analysis and the users feature tests**

Run: `composer phpstan`
Expected: no NEW errors (baseline errors are pre-existing).
Run: `./vendor/bin/phpunit tests/Feature/UserStatusDotTest.php tests/Feature/UsersTest.php`
Expected: PASS

- [ ] **Step 3: Commit (only if build artifacts are tracked; otherwise nothing to commit)**

Run: `git status --short public/build` — if this repo tracks build output, commit it; if ignored, skip.
