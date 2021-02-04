# Events Tracker
> A web based event calendar and tracking tool for music and arts communities.

Events Tracker is a CMS designed to be set up for a music or arts community to track events, weekly and monthly series, promoters, artists, producers, djs, venues and other entities.
Run an instance to create your own community where events, event series, entities, attendees and other objects can be added, tagged maintained and followed.  Keep the discussion going through a related discussion forum.  


## FEATURES (v0.2)

* Public, filterable event listings
* User registration for private homepage with customized private event listings and account settings.
* Enduser driven entity, event and relationship creation for venues, artists, musicians, promoters, etc.
* Event templating for creation of reoccurring events (series).
* Tagging events and entities for better categorization and searchability.
* Calendar layout of all events and recurring events
* Threaded events forum where posts can be linked to events, entities or topics.

### Built On

* Laravel 8
* MySQL 8 (can be database agnostic)

### FUTURE

In a later version we'll add intelligence to help event planning based on vectors such as region, venue, budget, audience, or other specific needs of any entity in the chain, as well as do planned crowd-sourcing or crowd funding.

## Installing / Getting started

* Clone the repo.
```bash
$ git clone git@github.com:geoff-maddock/events-tracker.git
$ cd events-tracker
$ composer install
```

### Configuration
* Configure .env based on .env.example
* Run `composer install`
* Run migrations:
  - php artisan migrate
* Seed database tables from existing seeds, or update the seed files with your own defaults:
  - php artisan db:seed
* Fix permissions with ./fix_permission.sh
* Point web server to /html/index.php


## Contributing

#### Bug Reports & Feature Requests

Please use the [issue tracker](https://github.com/geoff-maddock/events-tracker/issues) to report any bugs or file feature requests.

#### Developing
If you'd like to contribute, please fork the repository and use a feature
branch. Pull requests are warmly welcome.

## Author
Events Tracker was created by `Geoff Maddock`.  Send queries to geoff.maddock @ gmail.com.

## Licensing

The code in this project is licensed under MIT license.
