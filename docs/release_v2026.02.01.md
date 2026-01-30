# Release Notes v2026.02.01

**Release Date:** February 1, 2026  
**Previous Version:** v2025.01.01

This release contains a minor bug fix that improves the Instagram integration notification system. This is a maintenance release focused on improving the developer and administrator experience when troubleshooting Instagram posting failures.

---

## What's Changed

### BUG FIXES

#### Instagram Post Failure Email Improvements ([#1648](https://github.com/geoff-maddock/events-tracker/pull/1648))
* **Fixed:** Instagram post failure email now uses the event slug instead of event ID in the URL, making it easier to identify and access the specific event
* **Improved:** Added event slug to the email data for better event identification in notifications
* **Impact:** Administrators and developers will now receive more user-friendly failure notification emails with working direct links to events

**Technical Details:**
- Modified `AutomateInstagramPosts` command to pass event slug to failure email
- Updated `InstagramPostFailure` mailable to include slug parameter
- Changed email template to use slug-based URL (`/events/{slug}`) instead of ID-based URL (`/events/{id}`)

---

## Installation & Upgrade Notes

This is a drop-in replacement for v2025.01.01. No database migrations or configuration changes are required.

### For New Installations

Follow the standard installation process as documented in [deployment_notes.md](deployment_notes.md):

1. **Clone the repository:**
   ```bash
   git clone git@github.com:geoff-maddock/events-tracker.git
   cd events-tracker
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Install Node dependencies:**
   ```bash
   npm install
   ```

4. **Configure environment:**
   - Copy `.env.example` to `.env`
   - Configure your database connection
   - Add API keys for optional integrations (Instagram, Facebook, Twitter)
   
5. **Generate application key:**
   ```bash
   php artisan key:generate
   ```

6. **Run migrations:**
   ```bash
   php artisan migrate:fresh
   ```

7. **Seed the database:**
   ```bash
   # Choose one seeder based on your needs:
   php artisan db:seed --class=ProdBasicDatabaseSeeder      # Minimal data
   php artisan db:seed --class=ProdExtraDatabaseSeeder      # Full base data
   php artisan db:seed --class=ProdPittsburghDatabaseSeeder # Pittsburgh-specific
   ```

8. **Build frontend assets:**
   ```bash
   npm run prod
   ```

9. **Configure web server** (NGINX recommended) and **set up SSL** (Let's Encrypt)

10. **Set proper permissions:**
    ```bash
    sudo chmod 777 /var/www/events-tracker/storage/logs/laravel.log
    sudo chgrp -R www-data storage bootstrap/cache
    sudo chmod -R ug+rwx storage bootstrap/cache
    ```

### For Existing Installations (Upgrading from v2025.01.01)

1. **Backup your database and files**

2. **Pull the latest code:**
   ```bash
   git fetch --tags
   git checkout v2026.02.01
   ```

3. **Update dependencies:**
   ```bash
   composer install --no-dev
   npm install
   ```

4. **Rebuild frontend assets:**
   ```bash
   npm run prod
   ```

5. **Clear caches:**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   ```

6. **No database migrations required** for this release

---

## Current Feature Set

Events Tracker v2026.02.01 includes all features from v2025.01.01:

### Core Features
* **Event Management**: Public filterable and sortable event listings with built-in views by time, type, and topic
* **User System**: User registration with private homepages, customized event listings, and account settings
* **Entity Management**: User-driven creation and management of venues, artists, musicians, promoters, and more
* **Event Series**: Templating system for creating recurring events
* **Tagging System**: Categorize and improve searchability of events and entities
* **Calendar Views**: Visual calendar layout for all events and recurring series
* **Forum**: Threaded discussion forum with posts linkable to events, entities, or topics
* **Photo Module**: Display and manage images from events
* **Dark/Light Themes**: Default dark layout with optional light mode
* **SEO Enhancement**: Enhanced metadata in headers for improved search engine results
* **Media Integration**: Automatic transformation of Bandcamp and SoundCloud links into embedded players
* **iCal Export**: Export events to iCal format with static feed URLs
* **API**: RESTful API with Swagger UI documentation at `/api/docs`

### Integrations
* **Instagram**: Automated posting to Instagram accounts
* **Facebook**: Facebook integration for event sharing
* **Twitter**: Twitter integration for event announcements
* **S3**: File asset storage support for images

### Calendar Features (Added 2024.12.26)
* All events iCal feed: `https://your-domain.com/events/ical`
* Attending events feed: `https://your-domain.com/users/{user-id}/attending-ical`
* Interested events feed: `https://your-domain.com/users/{user-id}/interested-ical`

---

## Technical Stack

* **PHP**: 8.1+
* **Framework**: Laravel 10
* **Database**: MySQL 8 (can be database agnostic)
* **Frontend**: Bootstrap 5
* **Node.js**: 12.4+ required for asset building
* **Server**: NGINX (recommended)
* **SSL**: Let's Encrypt (recommended)

---

## Known Issues

* No known critical issues in this release
* See the [issue tracker](https://github.com/geoff-maddock/events-tracker/issues) for open feature requests and minor bugs

---

## Roadmap

Looking ahead to future releases:

* **Entity-to-Entity Relations**: Enhanced relationship mapping between entities
* **Menu Enhancement**: Improved navigation and user interface
* **Lightweight Frontend**: New frontend that consumes the API for better performance
* **Additional API Endpoints**: Continued expansion of the API for external integrations

---

## Contributing

If you'd like to contribute to Events Tracker:
1. Check the [issue tracker](https://github.com/geoff-maddock/events-tracker/issues) for open issues
2. Comment on the issue or contact the author before creating a PR
3. Follow the guidelines in [CONTRIBUTING.md](../CONTRIBUTING.md)

---

## Support

* **Bug Reports**: Use the [issue tracker](https://github.com/geoff-maddock/events-tracker/issues)
* **Questions**: Contact geoff.maddock@gmail.com
* **Documentation**: See [docs/](.) for detailed documentation

---

## License

Events Tracker is open-source software licensed under the [MIT license](../LICENSE).

---

## Acknowledgments

Thank you to all contributors and users who have helped improve Events Tracker since the v2025.01.01 release.

**Full Changelog**: https://github.com/geoff-maddock/events-tracker/compare/v2025.01.01...v2026.02.01
