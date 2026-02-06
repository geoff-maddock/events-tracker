# How to Create Release v2026.02.01

This document provides step-by-step instructions for creating the v2026.02.01 release on GitHub.

## Pre-Release Checklist

- [x] All code changes have been merged to the main branch
- [x] CHANGELOG.md has been updated with release notes
- [x] README.md version number has been updated
- [x] Release notes document has been created (docs/release_v2026.02.01.md)
- [ ] All tests pass on the main branch
- [ ] CI/CD pipeline is green
- [ ] Documentation is up to date

## Creating the Release on GitHub

### Step 1: Create and Push the Git Tag

From your local repository on the main branch:

```bash
# Ensure you're on the main branch and up to date
git checkout main
git pull origin main

# Create an annotated tag for the release
git tag -a v2026.02.01 -m "Release v2026.02.01 - Instagram notification improvements"

# Push the tag to GitHub
git push origin v2026.02.01
```

### Step 2: Create the GitHub Release

#### Option A: Using GitHub Web Interface

1. Go to https://github.com/geoff-maddock/events-tracker/releases/new

2. Fill in the release form:
   - **Choose a tag:** Select `v2026.02.01` from the dropdown (or type it if you haven't pushed the tag yet)
   - **Target:** main
   - **Release title:** `v2026.02.01 - Instagram Notification Improvements`
   - **Release description:** Copy the content from the "GitHub Release Notes" section below

3. Check the box for "Set as the latest release"

4. Click "Publish release"

#### Option B: Using GitHub CLI

If you have GitHub CLI (`gh`) installed:

```bash
# Create the release with notes from file
gh release create v2026.02.01 \
  --title "v2026.02.01 - Instagram Notification Improvements" \
  --notes-file docs/release_v2026.02.01.md \
  --latest
```

Or create it interactively:

```bash
gh release create v2026.02.01 --generate-notes
```

## GitHub Release Notes

Use the following markdown for the GitHub release description:

---

Release v2026.02.01 contains a bug fix that improves the Instagram integration notification system. This is a minor maintenance release focused on improving the developer and administrator experience when troubleshooting Instagram posting failures.

## What's Changed

### BUG FIXES

**Instagram Post Failure Email Improvements** ([#1648](https://github.com/geoff-maddock/events-tracker/pull/1648))
* Fixed Instagram post failure email to use event slug instead of event ID in URLs
* Added event slug to email data for better event identification in notifications  
* Administrators now receive more user-friendly failure notification emails with working direct links to events

**Technical Details:**
- Modified `AutomateInstagramPosts` command to pass event slug to failure email
- Updated `InstagramPostFailure` mailable to include slug parameter
- Changed email template to use slug-based URL (`/events/{slug}`) instead of ID-based URL (`/events/{id}`)

## Installation & Upgrade

### New Installations

See the full [deployment guide](https://github.com/geoff-maddock/events-tracker/blob/main/docs/deployment_notes.md) for complete installation instructions.

**Quick Start:**
```bash
git clone git@github.com:geoff-maddock/events-tracker.git
cd events-tracker
composer install
npm install
cp .env.example .env
# Configure .env with your settings
php artisan key:generate
php artisan migrate:fresh
php artisan db:seed --class=ProdExtraDatabaseSeeder
npm run prod
```

### Upgrading from v2025.01.01

This is a drop-in replacement with no database migrations required:

```bash
git fetch --tags
git checkout v2026.02.01
composer install --no-dev
npm install
npm run prod
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

## Features

Events Tracker v2026.02.01 includes all features from v2025.01.01:

* Public event listings with filtering and sorting
* User registration and private event feeds
* Entity management (venues, artists, promoters, etc.)
* Recurring event series with templating
* Calendar views and iCal exports
* Threaded discussion forum
* Photo galleries
* Dark/Light themes
* Instagram, Facebook, and Twitter integrations
* RESTful API with Swagger documentation
* S3 image storage support

**Technical Stack:** PHP 8.1+ | Laravel 10 | MySQL 8 | Bootstrap 5

## Documentation

* [Full Release Notes](https://github.com/geoff-maddock/events-tracker/blob/main/docs/release_v2026.02.01.md)
* [Deployment Guide](https://github.com/geoff-maddock/events-tracker/blob/main/docs/deployment_notes.md)
* [API Documentation](https://github.com/geoff-maddock/events-tracker/blob/main/docs/api_notes.md)
* [Contributing Guidelines](https://github.com/geoff-maddock/events-tracker/blob/main/CONTRIBUTING.md)

## Known Issues

No known critical issues in this release. See the [issue tracker](https://github.com/geoff-maddock/events-tracker/issues) for open items.

## What's Next

* Entity-to-entity relations
* Menu and navigation enhancements  
* Lightweight API-driven frontend
* Additional API endpoints

**Full Changelog**: https://github.com/geoff-maddock/events-tracker/compare/v2025.01.01...v2026.02.01

---

## Post-Release Tasks

After creating the release:

- [ ] Verify the release appears correctly on https://github.com/geoff-maddock/events-tracker/releases
- [ ] Test the release download links
- [ ] Update any deployment documentation if needed
- [ ] Announce the release (if applicable):
  - [ ] Social media
  - [ ] Project website
  - [ ] User mailing list
- [ ] Monitor issue tracker for any release-related problems

## Rollback Plan

If critical issues are discovered after release:

1. Document the issue in the GitHub issue tracker
2. Create a hotfix branch from the v2026.02.01 tag
3. Fix the issue and create a new release (v2026.02.02 or similar)
4. Communicate the issue and fix to users

Alternatively, if needed:
- Users can roll back to v2025.01.01: `git checkout v2025.01.01`

## Additional Notes

### Version Numbering

This project uses a date-based versioning scheme: `YYYY.MM.NN`
- Format: `vYYYY.MM.NN` where:
  - `YYYY` = Year (e.g., 2026)
  - `MM` = Month (e.g., 02 for February)
  - `NN` = Sequential release number for that month (01, 02, 03, etc.)
- Example: `v2026.02.01` is the first release in February 2026
- For multiple releases in the same month: `v2026.02.01`, `v2026.02.02`, `v2026.02.03`, etc.
- Releases are typically created on the first day of the month but can be created on any day

### Continuous Integration

This project uses continuous integration, so features are deployed as they're completed. Releases serve as:
- Official stable checkpoints
- Reference points for users
- Documentation milestones
- Rollback targets if needed

### Support

For questions or issues:
- GitHub Issues: https://github.com/geoff-maddock/events-tracker/issues  
- Email: geoff.maddock@gmail.com
