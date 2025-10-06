# Events Tracker - AI Agent Documentation

## Project Overview

**Events Tracker** is a Laravel-based CMS for music and arts communities to track events, venues, artists, series, and related entities. It provides public event listings, user registration, entity management, event templating for recurring series, tagging, calendar views, threaded forums, and photo galleries.

- **Repository**: geoff-maddock/events-tracker
- **Default Branch**: main
- **License**: MIT
- **Maintainer**: Geoff Maddock (geoff.maddock@gmail.com)
- **Version**: v2025.01.01 (Stable Release)

## Tech Stack

### Backend
- **PHP**: 8.1+
- **Framework**: Laravel 10
- **Database**: MySQL 8 (database agnostic)
- **Key Dependencies**:
  - Laravel Socialite (social authentication)
  - Laravel Sanctum (API authentication)
  - Spatie Laravel Sitemap
  - Intervention Image (image processing)
  - eluceo/ical (calendar exports)
  - League Flysystem (S3 file storage)
  - Sentry (error tracking)
  - Laravel Shield (API basic auth)

### Frontend
- **CSS Framework**: Bootstrap 5
- **JavaScript**: Vue.js 3, jQuery
- **Build Tools**: Laravel Mix, Webpack
- **UI Libraries**:
  - FullCalendar 6.1.11
  - Select2 4.1
  - Lightbox2
  - SweetAlert2
  - Dropzone 6
  - Moment.js

### Development Tools
- **Static Analysis**: PHPStan (Larastan)
- **Testing**: PHPUnit, Laravel Dusk
- **Debugging**: Laravel Debugbar, Laravel IDE Helper
- **CI/CD**: GitHub Actions, Jenkins

## Project Structure

### Core Directories
```
/var/www/dev-events/
├── app/
│   ├── Broadcasting/       # Broadcasting channels
│   ├── Console/           # Artisan commands
│   ├── Events/            # Event classes
│   ├── Exceptions/        # Exception handlers
│   ├── Filters/           # Query filters
│   ├── Handlers/          # Event handlers
│   ├── Http/
│   │   ├── Controllers/   # Request controllers
│   │   ├── helpers.php    # Helper functions
│   │   └── Flash.php      # Flash message handler
│   ├── Listeners/         # Event listeners
│   ├── Mail/              # Mail classes
│   ├── Models/            # Eloquent models (see below)
│   ├── Notifications/     # Notification classes
│   ├── Policies/          # Authorization policies
│   ├── Providers/         # Service providers
│   ├── Services/          # Business logic services
│   └── Traits/            # Reusable traits
├── bootstrap/             # App initialization
├── config/                # Configuration files
├── database/
│   ├── factories/         # Model factories
│   ├── initialize/        # Initialization scripts
│   ├── migrations/        # Database migrations
│   ├── queries/           # SQL queries
│   ├── schema/            # Database schema
│   └── seeders/           # Database seeders
├── docs/                  # Documentation
├── public/                # Web root (assets, entry point)
├── resources/             # Views, raw assets
├── routes/                # Route definitions
│   ├── api.php           # API routes
│   ├── channels.php      # Broadcasting channels
│   ├── console.php       # Console routes
│   └── web.php           # Web routes
├── storage/               # Logs, cache, uploaded files
├── tests/                 # Test suite
└── vendor/                # Composer dependencies
```

### Key Models
The application manages several interconnected entities:

**Core Event Models**:
- `Event` - Individual events with dates, venues, entities
- `Series` - Recurring event templates (weekly/monthly)
- `EventType` - Categories (concert, festival, etc.)
- `EventStatus` - Event states
- `EventResponse` - User RSVPs
- `EventReview` - User event ratings/reviews

**Entity Models**:
- `Entity` - Flexible model for venues, artists, promoters, DJs, producers
- `EntityType` - Entity categories
- `EntityStatus` - Entity states

**Location Models**:
- `Location` - Geographic locations
- `LocationType` - Location categories

**Social/Community Models**:
- `User` - Registered users
- `Follow` - User follows for events/entities
- `Like` - User likes
- `Comment` - Comments on events/entities
- `Thread` - Forum discussions
- `Post` - Forum posts
- `ThreadCategory` - Forum categories

**Content Models**:
- `Photo` - Event/entity photos
- `Link` - External links
- `Tag` - Categorization tags
- `TagType` - Tag categories
- `Blog` - Blog posts

**System Models**:
- `Role` - User roles
- `Permission` - Access permissions
- `Group` - User groups
- `Visibility` - Content visibility settings

### Controllers
- `EventsController` - Event CRUD operations
- `SeriesController` - Series management
- `EntitiesController` - Entity management
- `VenuesController` - Venue-specific operations
- `ThreadsController` - Forum functionality
- `PhotosController` - Photo management
- `UsersController` - User administration
- `CalendarController` - Calendar views
- API controllers in `app/Http/Controllers/Api/`

## Development Workflow

### Initial Setup
```bash
# Clone repository
git clone git@github.com:geoff-maddock/events-tracker.git
cd events-tracker

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Configure environment
cp .env.example .env
# Edit .env with database credentials, app settings

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate:fresh

# Seed database (choose one):
php artisan db:seed --class=ProdBasicDatabaseSeeder      # Minimal data
php artisan db:seed --class=ProdExtraDatabaseSeeder      # More complete data
php artisan db:seed --class=ProdPittsburghDatabaseSeeder # Pittsburgh-specific data

# Build frontend assets
npm run dev    # Development
npm run prod   # Production
```

### Database Requirements
- MySQL 8.0+ (or compatible database)
- Required PHP extensions: `pdo_mysql`, `zip`
- Create database and user with full privileges
```sql
CREATE DATABASE events_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'events_user'@'%' IDENTIFIED BY 'password';
GRANT ALL ON events_tracker.* TO 'events_user'@'%';
FLUSH PRIVILEGES;
```

### Environment Configuration
Key `.env` variables:
- `APP_*` - Application settings (name, environment, debug)
- `DB_*` - Database connection
- `MAIL_*` - Email settings
- `AWS_*` - S3 storage for images (optional)
- `FACEBOOK_*`, `TWITTER_*` - Social integration (optional)
- `SENTRY_*` - Error tracking (optional)
- Laravel Shield credentials for API basic auth

### Running the Application
```bash
# Development server
php artisan serve

# Watch and rebuild assets
npm run watch

# Run tests
php artisan test
./vendor/bin/phpunit

# Static analysis
./vendor/bin/phpstan analyse
```

## API Documentation

### Authentication
The API supports two authentication methods:

1. **Basic Auth**: Include `Authorization: Basic base64(username:password)` header
2. **Token Auth**: Include `Authorization: Bearer {token}` header
   - Acquire token via `POST /api/auth/token` with basic auth and `token_name` in body

### API Endpoints
- Visit `/api/docs` for Swagger-generated documentation
- Documentation generated from `public/postman/` folder

### Filtering & Sorting
Query parameters for list endpoints:
- `filters[field]=value` - Filter by field
- `filters[tag]=music,art` - Filter by any tag
- `filters[tag_all]=music,art` - Filter by all tags
- `sort=field&direction=asc` - Sort results

Example: `GET /api/events?filters[name]=Concert&filters[tag]=music&sort=start_at&direction=desc`

## Key Features

### Event Management
- Create, edit, delete events
- Event templates for recurring series
- Multiple event types and statuses
- Tag-based categorization
- Venue and entity associations
- Photo uploads with S3 support
- RSVPs and event reviews
- iCal export for calendar integration

### Entity System
- Flexible entity model for venues, artists, promoters, DJs, producers
- Entity relationships to events
- Entity following
- Entity-specific pages and profiles

### Social Features
- User registration and authentication
- Social login (Facebook, optional)
- User profiles with custom event views
- Event following
- Commenting system
- Threaded forum discussions
- Like functionality

### Content Features
- Rich text event descriptions
- Photo galleries with Lightbox
- Embedded audio from Bandcamp/SoundCloud
- Calendar view (FullCalendar)
- Responsive design (dark/light themes)
- SEO-optimized headers
- Sitemap generation

## Testing

### Test Suite
- PHPUnit for unit and feature tests
- Laravel Dusk for browser tests
- Run: `php artisan test` or `./vendor/bin/phpunit tests`

### Static Analysis
- PHPStan/Larastan for code quality
- Configuration: `phpstan.neon`, `phpstan.neon.dist`
- Baseline: `phpstan-baseline.neon`
- Run: `composer phpstan`

## Deployment

### Server Requirements
- Ubuntu LEMP stack recommended
- 2+ vCPUs, 4GB+ RAM
- PHP 8.1+ with required extensions
- MySQL 8.0+
- Node.js 14.15+
- SSL certificate for production

### Deployment Steps
1. Provision server and database
2. Clone repository
3. Configure `.env` file
4. Run `composer install --no-dev --optimize-autoloader`
5. Run `npm install && npm run prod`
6. Run migrations and seeders
7. Configure web server (Nginx/Apache)
8. Set up SSL
9. Configure DNS
10. Set proper file permissions for `storage/` and `bootstrap/cache/`

See `docs/deployment_notes.md` for detailed instructions.

## Contributing

### Contribution Policy
- **Bug Reports**: Check existing issues first, then open new issue with details
- **Feature Requests**: Contact maintainer before opening issue
- **Pull Requests**: Discuss with maintainer first, ensure PR describes problem/solution
- **Questions**: Email geoff.maddock@gmail.com

### Code Standards
- Follow Laravel conventions
- Pass PHPStan analysis
- Write tests for new features
- Update documentation for significant changes

## Important Notes for AI Agents

### Code Modification Guidelines
1. **Model Relationships**: Events, Entities, and Series have complex many-to-many relationships. Check existing relationships before adding new ones.
2. **Authorization**: Uses Laravel policies. Check `app/Policies/` before modifying access control.
3. **Query Filters**: Uses custom `QueryFilter` class in `app/Filters/`. Apply filters consistently.
4. **File Storage**: Images can be stored locally or in S3. Check `config/filesystems.php`.
5. **Seeding**: Multiple seeders for different deployment scenarios. Don't modify production seeders without understanding implications.
6. **API Routes**: Separate from web routes. API uses Sanctum tokens or basic auth.
7. **Frontend**: Uses Laravel Mix for asset compilation. Changes to JS/CSS require rebuild.
8. **Migrations**: Always create new migration files, never edit existing ones.

### Common Patterns
- **Event Queries**: Use scopes like `Event::future()`, `Event::past()`, `Event::visible($user)`
- **Tags**: Many models use polymorphic `tags` relationship
- **Photos**: Polymorphic relationship for multiple model types
- **Visibility**: All content has visibility settings (public, private, etc.)
- **Soft Deletes**: Many models use soft deletion
- **User Tracking**: `created_by`, `updated_by` fields track user actions

### Known Issues & Current Work
- Current branch `series-playlist-fix` suggests work on series functionality
- Check GitHub issues for active bugs and features
- CI/CD via GitHub Actions - ensure tests pass

### Additional Documentation
- `docs/deployment_notes.md` - Detailed deployment guide
- `docs/api_notes.md` - API usage examples
- `docs/feature_notes.md` - Feature release notes
- `docs/OembedExtractor*.md` - oEmbed functionality documentation
- `CHANGELOG.md` - Version history
- `CONTRIBUTING.md` - Contribution guidelines
- `SECURITY.md` - Security policies

## Contact & Support
- **Maintainer**: Geoff Maddock
- **Email**: geoff.maddock@gmail.com
- **Issues**: https://github.com/geoff-maddock/events-tracker/issues
- **Repository**: https://github.com/geoff-maddock/events-tracker
