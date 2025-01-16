 [![Build](https://github.com/geoff-maddock/events-tracker/actions/workflows/php.yml/badge.svg)](https://github.com/geoff-maddock/events-tracker/actions/workflows/php.yml)

# Events Tracker
> A web based event calendar and tracking tool for music and arts communities.

Events Tracker is a CMS designed to be set up for a music or arts community to track events, weekly and monthly series, promoters, artists, producers, djs, venues and other entities.

Run an instance to create your own community where events, event series, entities, attendees and other objects can be added, tagged maintained and followed.  Keep the discussion going through a related discussion forum.  


## FEATURES (v2025.01.01 - Stable Release)

* Public filterable and sortable event listings, with built in views by time, type and topic.
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

Read new feature release notes in the [changelog](docs/feature_notes.md).

### Built On
* PHP 8.1
* Laravel 10
* MySQL 8 (can be database agnostic)
* Bootstrap 5
* Optional Integrations: Facebook, Twitter

### Roadmap
* Adding to the site API to facilitate external embedding of event and entity data.
* Building a more lightweight frontend that connects to the API

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
* Configure .env based on .env.example
* Run `composer install`
* Run `npm install`
* Run migrations to create the initial database.
  - php artisan migrate:fresh
* Seed database tables from one of the provided default seeders.  Only run this when starting the production app the first time.
  - ```php artisan db:seed --class=ProdBasicDatabaseSeeder```
    - The most basic data to run the app, some additional config will be required.
  - ```php artisan db:seed --class=ProdExtraDatabaseSeeder```
    - This includes base data for all modules and more fleshed out permissions.  No specific content.
  - ```php artisan db:seed --class=ProdPittsburghDatabaseSeeder```
    - This includes everything in the ProdExtra seeder, plus some base specific data for Pittsburgh.
* Set up DNS entry for your domain
* Configure web server and SSL
* Run node build for your environment
  - ```npm run dev```
  - ```npm run prod```

#### API Configuration
* Generate basic auth u/p and configure in .env as per [Laravel Shield](https://github.com/vinkla/laravel-shield)

##### API Documentation
* [API Documentation](docs/api_notes.md)

#### Bug Reports & Feature Requests

Please use the [issue tracker](https://github.com/geoff-maddock/events-tracker/issues) to report any bugs or file feature requests.

#### Developing
If you'd like to contribute, please comment on the issue or contact the author before creating a PR.
Once the stable version is released, I'll add more notes and welcome public contributions.


## Author
Events Tracker was created by `Geoff Maddock`.  Send queries to geoff.maddock @ gmail.com.

## Licensing

The code in this project is licensed under MIT license.
