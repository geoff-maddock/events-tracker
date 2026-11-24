### Documentation brought up to date (#2414)

No code changes.

- `README.md`: PHP 8.2+ and Laravel 12 (it said 8.1 and 10); the API section describes basic auth and tokens instead of Laravel Shield, which was removed long ago; the install steps use `php artisan migrate` and `npm run build`.
- `docs/deployment_notes.md`: the server is Ubuntu 22.04; the log file is group-writable, not `chmod 777`; series event creation is listed as a command run by hand, not as scheduled; a new "Backups" section says what needs backing up, what production does, and how to restore. The one-time "Upgrading to v2026.05.01" steps moved into that release's notes.
- `docs/release_process.md` replaces `docs/RELEASE_PROCESS_v2026.05.01.md`: the steps for any release, on `main`, including folding `docs/unreleased/` into the feature notes.
- `docs/release_v2026.05.01.md`: that release ran Laravel 10 (it said 11) and the branch is `main`.
- `CHANGELOG.md` says where the changes since the last release are listed, and no longer claims semantic versioning.
- Removed: `docs/superpowers/` (seven plan and design files for features that shipped), `docs/frontend-integration-example.md` (a React tutorial for the retired separate frontend) and `RELEASE_SUMMARY.md` (a leftover from preparing the May release). `docs/api-register-frontend-url.md` stays, since the API still accepts `frontend-url`, with its React-specific framing reduced.
