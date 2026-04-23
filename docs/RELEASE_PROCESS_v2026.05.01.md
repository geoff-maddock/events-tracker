# How to Create Release v2026.05.01

This document provides step-by-step instructions for creating the v2026.05.01 release on GitHub.

## Pre-Release Checklist

- [x] All code changes have been merged to the main branch
- [x] CHANGELOG.md has been updated with release notes
- [x] Release notes document created (`docs/release_v2026.05.01.md`)
- [x] Deployment notes updated with upgrade steps
- [ ] All tests pass on the main branch (`./vendor/bin/phpunit tests`)
- [ ] CI/CD pipeline is green
- [ ] Documentation is up to date

## Creating the Release on GitHub

### Step 1: Create and Push the Git Tag

From your local repository on the main branch:

```bash
# Ensure you're on the main branch and up to date
git checkout master
git pull origin master

# Create an annotated tag for the release
git tag -a v2026.05.01 -m "Release v2026.05.01"

# Push the tag to GitHub
git push origin v2026.05.01
```

### Step 2: Create the GitHub Release

#### Option A: Using GitHub Web Interface

1. Go to https://github.com/geoff-maddock/events-tracker/releases/new
2. Fill in the release form:
   - **Choose a tag:** Select `v2026.05.01`
   - **Target:** master
   - **Release title:** `v2026.05.01 - Major Feature Release`
   - **Release description:** Copy the content from `docs/release_v2026.05.01.md`
3. Check "Set as the latest release"
4. Click "Publish release"

#### Option B: Using GitHub CLI

```bash
gh release create v2026.05.01 \
  --title "v2026.05.01 - Major Feature Release" \
  --notes-file docs/release_v2026.05.01.md \
  --latest
```

## Post-Release Tasks

- [ ] Verify the release appears at https://github.com/geoff-maddock/events-tracker/releases
- [ ] Test the release download links
- [ ] Deploy to production using the steps in `docs/deployment_notes.md#upgrading-to-v202605`
- [ ] Confirm the 5 migrations ran successfully on production
- [ ] Verify the Vite-built assets are loading correctly
- [ ] Monitor error logs for the first 24 hours

## Rollback Plan

If critical issues are discovered after release:

1. Document the issue in the GitHub issue tracker
2. Create a hotfix branch from the `v2026.05.01` tag
3. Fix the issue and release as `v2026.05.02`
4. Users can roll back to `v2025.01.01` if needed: `git checkout v2025.01.01`

Note: rolling back will require reverting the 5 database migrations manually if they have already been applied.

## Version Numbering

This project uses date-based versioning: `vYYYY.MM.NN`
- `YYYY` = Year, `MM` = Month, `NN` = sequential release number for that month
- Example: `v2026.05.01` is the first release in May 2026

## Support

- GitHub Issues: https://github.com/geoff-maddock/events-tracker/issues
- Email: geoff.maddock@gmail.com
