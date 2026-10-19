# Sentry Triage Report — 2026-10-19

**Auth:** OK (org endpoint returned 200).
**Scope:** `geoff-maddock/eventrepo`, unresolved issues by frequency (9 total).
**PRs opened this run: 0** (no unfixed, in-repo code defects — details below).

## Verdict

No pull requests were warranted this run. Every Sentry issue that is a *code*
defect is **already fixed in `main`** (just awaiting/having-just-received a
deploy). The remaining high-volume issues are **deployment/environment
problems that cannot be fixed with a code change** — and two of them are still
firing in production right now. Those need an ops action, not a PR.

## Still firing in production — needs ops attention (NOT code-fixable)

| Short-ID | Count | Last seen | Problem | Likely cause / fix |
|----------|-------|-----------|---------|--------------------|
| EVENTREPO-W4 | 4 | **2026-10-19 (today)** | `Vite manifest not found at public/build/manifest.json` | Production is missing the built frontend assets. Run `npm run build` (alias `npm run prod`) as part of deploy and ensure `public/build/` is deployed. (Same root cause as EVENTREPO-V1 and -YK.) |
| EVENTREPO-YZ | 8 | 2026-10-15 | `file_put_contents(storage/framework/cache/...): Permission denied` | The web/php-fpm user can't write to `storage/`. Fix ownership/permissions on `storage/` and `bootstrap/cache` (e.g. `chown -R www-data storage bootstrap/cache && chmod -R ug+rwX`). |

These two (plus V1, YK) account for the largest error volume and are the real
production pain. The error-triage routine can't open a PR that fixes them.

## Code defects — already fixed in `main` (no PR needed)

| Short-ID | Count | Last seen | Issue | Fixed by |
|----------|-------|-----------|-------|----------|
| EVENTREPO-Z0 | 4 | 2026-10-13 | `Undefined property LengthAwarePaginator::$elements` on `/users/signups` | f7bcc23 (#2315) — deleted the broken `components/ui/pagination.blade.php`; vendor pagination now guards `@if (isset($elements))`. |
| EVENTREPO-YV | 2 | 2026-10-13 | `Call to undefined method InstagramEventPoster::weekendPreviewEventIds()` | b367259 (#2290) — method added (now `InstagramEventPoster.php:230`). Event predates the deploy. |
| EVENTREPO-YJ | 3 | 2026-09-23 | `Unknown column 'test' in order clause` from `/threads/all?sort=test` (scanner probe) | 972c943 (#2254) — `ListEntityResultBuilder::setAllowedSortFields()` + `isValidSortField()` allowlist. Unknown sort columns now fall back to the default. Occurred once (3s burst) before the fix; no recurrence. |

If Z0/YV are still appearing after the next deploy, re-investigate; otherwise
they can be resolved in Sentry.

## Skipped (not actionable code fixes)

- **EVENTREPO-V1 (8), EVENTREPO-YK (1)** — same "Vite manifest not found" as W4 (deployment).
- **EVENTREPO-Z1 (2)** — `RuntimeException: No stories could be posted...` is an *intentional* guard in `InstagramEventPoster::postEventStories` thrown when the selected events have no photos. A data/usage condition, not a defect.
- **EVENTREPO-YH (2)** — N+1 query warning on `/events`. A performance note, not a crash; last seen 2026-09-22. Touching the hot events list path is a broader change than this routine should make autonomously without review.

## Recommended next steps (for a human)

1. Fix the deploy pipeline so `npm run build` runs and `public/build/` ships → clears W4 / V1 / YK.
2. Correct `storage/` + `bootstrap/cache` permissions for the web user → clears YZ.
3. Deploy current `main` if not already → clears Z0 and YV; then resolve those in Sentry.
