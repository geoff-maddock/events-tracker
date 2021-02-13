# Events Tracker
> A web based event calendar and tracking tool for music and arts communities.

Events Tracker is a CMS designed to be set up for a music or arts community to track events, weekly and monthly series, promoters, artists, producers, djs, venues and other entities.

Run an instance to create your own community where events, event series, entities, attendees and other objects can be added, tagged maintained and followed.  Keep the discussion going through a related discussion forum.  


## FEATURES (v2021.2.1-BETA)

* Public filterable and sortable event listings, with built in views by time, type and topic.
* Event creation, editing and monitoring for registered users.
* User registration for private homepage with customized private event listings and account settings.
* Enduser driven entity, event and relationship creation for venues, artists, musicians, promoters, etc.
* Event templating for creation of reoccurring events (series).
* Tagging events and entities for better categorization and searchability.
* Calendar layout of all events and recurring events
* Threaded events forum where posts can be linked to events, entities or topics.

### Built On
* PHP 7.4
* Laravel 8
* MySQL 8 (can be database agnostic)
* Bootstrap 3.x
* Optional Integrations: Facebook, Twitter

### FUTURE
The medium term goal is to get the project from Beta to a Stable release version with very clear deployment notes.  After that there are various improvements including a fully dockerized version, a UI revamp, and improvements with how users can follow events and entities.
In a later version we'll add intelligence to help event planning based on vectors such as region, venue, budget, audience, or other specific needs of any entity in the chain, as well as do planned crowd-sourcing or crowd funding.

## Installing / Getting started

Read the [full deployment notes](docs/deployment_notes.md) for the project for a more in depth installation guide.

* Clone the repo.
```bash
$ git clone git@github.com:geoff-maddock/events-tracker.git
$ cd events-tracker
```

* Install the dependencies.
```
$ composer install
```

### Configuration
* Configure .env based on .env.example
* Run `composer install`
* Run migrations to create the initial database.
  - php artisan migrate:fresh
* Seed database tables from one of the provided default seeders.  Only run this when starting the production app the first time.
  - ```php artisan db:seed --class=ProdBasicDatabaseSeeder```
    - The most basic data to run the app, some additional config will be required.
  - ```php artisan db:seed --class=ProdExtraDatabaseSeeder```
    - This includes base data for all modules and more fleshed out permissions.  No specific content.
  - ```php artisan db:seed --class=ProdPittsburghDatabaseSeeder```
    - This includes everything in the ProdExtra seeder, plus some base specific data for Pittsburgh.
* Fix permissions with ./fix_permission.sh
* Point web server to /html/index.php

#### Bug Reports & Feature Requests

Please use the [issue tracker](https://github.com/geoff-maddock/events-tracker/issues) to report any bugs or file feature requests.

#### Developing
If you'd like to contribute, please comment on the issue or contact the author before creating a PR.
Once the stable version is released, I'll add more notes and welcome public contributions.


## Author
Events Tracker was created by `Geoff Maddock`.  Send queries to geoff.maddock @ gmail.com.

## Licensing

The code in this project is licensed under MIT license.
