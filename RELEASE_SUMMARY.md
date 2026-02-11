# Release v2026.02.01 - Summary and Next Steps

## Summary

This PR prepares all necessary documentation and updates for creating release version **v2026.02.01** of the Events Tracker application.

### What This Release Includes

This is a **minor maintenance release** that includes one bug fix merged since the last release v2025.01.01:

- **PR #1648**: Fixed Instagram post failure email to use event slug instead of event ID in URLs
  - Improves accessibility and usability of Instagram error notifications
  - Provides working direct links to events in failure emails
  - Better event identification for administrators

### Changes Made in This PR

1. **Created Release Notes** (`docs/release_v2026.02.01.md`)
   - Comprehensive release notes documenting the bug fix
   - Installation instructions for new deployments
   - Upgrade instructions for existing installations
   - Complete feature list
   - Technical stack information
   - Roadmap for future releases

2. **Updated CHANGELOG.md**
   - Added v2026.02.01 section with bug fix details
   - Updated version comparison links
   - Maintains proper changelog format

3. **Updated README.md**
   - Changed version reference from v2025.01.01 to v2026.02.01
   - Keeps readme in sync with current release

4. **Created Release Process Guide** (`docs/RELEASE_PROCESS_v2026.02.01.md`)
   - Step-by-step instructions for creating the GitHub release
   - Pre-release checklist
   - Command-line examples for git tagging
   - GitHub web UI instructions
   - GitHub CLI alternative
   - Pre-formatted release notes for GitHub
   - Post-release tasks
   - Rollback plan if needed

## What You Need to Do Next

To complete the release, follow these steps:

### 1. Review and Merge This PR

Review the documentation changes in this PR and merge to the main branch.

### 2. Run Tests (Optional but Recommended)

Ensure the CI pipeline is green and all tests pass:
```bash
./vendor/bin/phpunit tests
```

### 3. Create the Git Tag

Once this PR is merged to main:

```bash
git checkout main
git pull origin main
git tag -a v2026.02.01 -m "Release v2026.02.01 - Instagram notification improvements"
git push origin v2026.02.01
```

### 4. Create the GitHub Release

You have two options:

**Option A: Use GitHub Web Interface**
1. Go to https://github.com/geoff-maddock/events-tracker/releases/new
2. Select tag `v2026.02.01`
3. Use title: `v2026.02.01 - Instagram Notification Improvements`
4. Copy release notes from `docs/RELEASE_PROCESS_v2026.02.01.md` (GitHub Release Notes section)
5. Set as latest release
6. Publish

**Option B: Use GitHub CLI**
```bash
gh release create v2026.02.01 \
  --title "v2026.02.01 - Instagram Notification Improvements" \
  --notes-file docs/release_v2026.02.01.md \
  --latest
```

### 5. Verify the Release

- Check that the release appears at https://github.com/geoff-maddock/events-tracker/releases
- Verify download links work
- Confirm release notes display correctly

### 6. Optional: Announce the Release

If applicable:
- Update project website
- Post to social media
- Notify users via mailing list

## Files Changed

```
├── CHANGELOG.md                           (Updated: Added v2026.02.01 entry)
├── README.md                              (Updated: Version number)
├── docs/
│   ├── release_v2026.02.01.md            (New: Comprehensive release notes)
│   └── RELEASE_PROCESS_v2026.02.01.md    (New: Release creation guide)
```

## Key Points

✅ **No Breaking Changes**: This is a drop-in replacement for v2025.01.01

✅ **No Database Migrations**: No schema changes required

✅ **No Configuration Changes**: Existing .env files work as-is

✅ **Single Bug Fix**: Only Instagram notification improvement included

✅ **Fully Documented**: Comprehensive documentation for users and maintainers

📝 **Note on CHANGELOG**: The project historically used GitHub Releases for detailed changelogs. The CHANGELOG.md file has been updated for v2026.02.01, but previous releases (like v2025.01.01) may only be documented in GitHub Releases. See: https://github.com/geoff-maddock/events-tracker/releases

## Reference Documentation

- **Full Release Notes**: See `docs/release_v2026.02.01.md`
- **Release Process**: See `docs/RELEASE_PROCESS_v2026.02.01.md`
- **Deployment Guide**: See `docs/deployment_notes.md`
- **Previous Release**: https://github.com/geoff-maddock/events-tracker/releases/tag/v2025.01.01

## Questions?

If you have any questions about the release process or documentation:
- Review the detailed guides in the `docs/` folder
- Check the previous release (v2025.01.01) as a reference
- Contact: geoff.maddock@gmail.com

---

**Thank you for maintaining Events Tracker!** 🎉
