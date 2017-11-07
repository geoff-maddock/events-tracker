# Event Repo
> A guide and calender of events, weekly and monthly series, promoters, artists, producers, djs, venues and other entities.

A guide and calender of events, weekly and monthly series, promoters, artists, producers, djs, venues and other entities.
Run an instance to create your own community where events, event series, entities, attendees and other objects can be added, tagged maintained and followed.  


## FEATURES (v0.1)

* Public, filterable event listings
* User registration for private homepage with customized private event listings and account settings.
* Enduser driven entity, event and relationship creation for venues, artists, musicians, promoters, etc.
* Event templating for creation of reoccurring events (series).
* Tagging events and entities for better categorization and searchability.
* Calendar layout of all events and recurring events

### REQUIREMENTS

* Laravel 5.1+
* Database flexible
* Unix like operating system (OS X, Ubuntu, Debian)

### FUTURE

In a later version we'll add intelligence to help event planning based on vectors such as region, venue, budget, audience, or other specific needs of any entity in the chain, as well as do planned crowd-sourcing or crowd funding.

## Installing / Getting started

* Clone the repo.
```bash
$ git clone git@github.com:geoff-maddock/events-tracker.git
$ cd events-tracker
$ composer update
```

### Configuration
* Configure .env based on .env.example
* Run `composer update`
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
Events Repo was created by `Geoff Maddock`.  Send queries to geoff.maddock @ gmail.com.

## Licensing

The code in this project is licensed under MIT license.
