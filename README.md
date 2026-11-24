 [![Build](https://github.com/geoff-maddock/events-tracker/actions/workflows/php.yml/badge.svg)](https://github.com/geoff-maddock/events-tracker/actions/workflows/php.yml)

# Events Tracker
> A web based event calendar and tracking tool for music and arts communities.

Events Tracker is a CMS designed to be set up for a music or arts community to track events, weekly and monthly series, promoters, artists, producers, djs, venues and other entities.

Run an instance to create your own community where events, event series, entities, attendees and other objects can be added, tagged maintained and followed.  Keep the discussion going through a related discussion forum.  


## Features

* Public filterable and sortable event listings, with built in views by time, type and tags.
* Event creation, editing and following for registered users.
* User registration for private homepage with customized private event listings and account settings.
* Enduser driven entity, event and relationship creation for venues, artists, musicians, promoters, etc.
* Event templating for creation of reoccurring events (series).
* Tagging events and entities for better categorization and searchability.
* Calendar layout of all events and recurring events
* Threaded events forum where posts can be linked to events, entities or topics.
* Photos module to display images from events.
* Default dark layout with light option.
* Added enhanced data to the headers to improve search results and return site events on google.
* Links to bandcamp and soundcloud audio dynamically transform to widget players.
* File asset storage for images in S3 config.
* Export events to ical format or refer to a static link for a feed.
* (Optional) Instagram integration to pull in posts from a specific account and tag them to events and entities.

Changes are listed in the [feature notes](docs/feature_notes.md); changes since the last entry there are in [docs/unreleased](docs/unreleased).

### Built On
* PHP 8.2 or newer (CI and production run 8.4)
* Laravel 12
* MySQL 8
* Blade views with Tailwind CSS 4, built with Vite (Node 24)
* Optional integrations: Instagram, Discord, S3 file storage, Sentry

### Roadmap
* Improved experience on mobile
* Additional tools for promoters, artists and fans

## Installing / Getting started

Read the [full deployment notes](docs/deployment_notes.md) for the project for a more in depth installation guide.

* Clone the repo.
```bash
$ git clone git@github.com:geoff-maddock/events-tracker.git
$ cd events-tracker
```

* Install the PHP dependencies.
```
$ composer install
```

* Install node dependencies
```
$ npm install
```

### Configuration
* Copy `.env.example` to `.env`, fill in the database settings, and run `php artisan key:generate`
* Run migrations to create the initial database.
  - ```php artisan migrate```
* Seed database tables from one of the provided default seeders.  Only run this when starting the production app the first time.
  - ```php artisan db:seed --class=ProdBasicDatabaseSeeder```
    - The most basic data to run the app, some additional config will be required.
  - ```php artisan db:seed --class=ProdExtraDatabaseSeeder```
    - This includes base data for all modules and more fleshed out permissions.  No specific content.
  - ```php artisan db:seed --class=ProdPittsburghDatabaseSeeder```
    - This includes everything in the ProdExtra seeder, plus some base specific data for Pittsburgh.
* Set up DNS entry for your domain
* Configure web server and SSL
* Build the frontend
  - ```npm run dev``` (Vite dev server, for development)
  - ```npm run build``` (production build)

To update a running install, see "Deploying to production" in the [deployment notes](docs/deployment_notes.md).

#### API
The API needs no separate configuration. A request signs in with a user's email and password over HTTP basic auth, or with a token from `POST /api/tokens/create`.

* [API notes and examples](docs/api_notes.md)
* Every endpoint is listed at `/api/docs` on a running install

#### Bug Reports & Feature Requests

Please use the [issue tracker](https://github.com/geoff-maddock/events-tracker/issues) to report any bugs or file feature requests.

#### Developing
If you'd like to contribute, please comment on the issue or contact the author before creating a PR. See [CONTRIBUTING.md](CONTRIBUTING.md); pull requests go to `main`.


## Author
Events Tracker was created by `Geoff Maddock`.  Send queries to geoff.maddock @ gmail.com.

## Licensing

The code in this project is licensed under MIT license.
