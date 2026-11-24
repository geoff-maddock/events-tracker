# Release process

`main` is always what production runs: every merged pull request is deployed with `bin/deploy`
(see `docs/deployment_notes.md`). A release is a named point on `main` for people running their
own install. It changes nothing in production.

Versions are dates: `vYYYY.MM.NN`, where `NN` counts the releases of that month. `v2026.05.01`
is the first release of May 2026.

## Before the release

- [ ] CI is green on the commit of `main` being released.
- [ ] Fold the unreleased notes into `docs/feature_notes.md`. Its headings are dates
      (`## 2026.09.17`), so add one for the release date, with the version beside it
      (`## 2026.12.03 (v2026.12.01)`), directly below the `## Unreleased` section. Move the
      entries of "Unreleased" and the content of every file in `docs/unreleased/` (except its
      `README.md`) under the new heading, and delete those files. The `## Unreleased` heading
      stays, empty.
- [ ] Add a `## [YYYY.MM.NN] - YYYY-MM-DD` section to `CHANGELOG.md` with the summary of the
      release, and update the comparison links at the bottom of the file.
- [ ] Write `docs/release_vYYYY.MM.NN.md`: what changed, and what someone upgrading has to do
      (PHP or Node version changes, new required `.env` settings, migrations that take long or
      remove data, new cron or worker set-up). `docs/release_v2026.05.01.md` is the model.
- [ ] Merge that as a pull request.

## Tag and publish

```bash
git checkout main
git pull --ff-only origin main

git tag -a vYYYY.MM.NN -m "Release vYYYY.MM.NN"
git push origin vYYYY.MM.NN

gh release create vYYYY.MM.NN \
  --title "vYYYY.MM.NN" \
  --notes-file docs/release_vYYYY.MM.NN.md \
  --latest
```

Or on the web: https://github.com/geoff-maddock/events-tracker/releases/new, choose the tag,
target `main`, and paste the release notes.

## After

- [ ] The release is listed at https://github.com/geoff-maddock/events-tracker/releases.
- [ ] `docs/unreleased/` holds only its `README.md`.

## If a release is broken

Fix it on `main` like any other bug and publish the next number (`vYYYY.MM.02`). There are no
release branches.

Someone who must go back restores the database backup they took before upgrading and checks
out the previous tag. Checking out the older code alone is not enough, because the migrations
the newer release ran stay in the database. `php artisan migrate:rollback` is not a safe
substitute: several migrations remove duplicate rows or rename data on the way up and do not
put it back on the way down. Release notes should therefore always tell upgraders to back up
first.

## Past releases

| Version | Notes |
| --- | --- |
| v2026.05.01 | `docs/release_v2026.05.01.md` |
| v2025.01.01, v2022.09.01, v2021.11.28 | GitHub releases page |
