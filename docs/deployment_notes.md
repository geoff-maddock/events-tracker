# Documentation

## Installing / Getting started

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
* Run node build for your environment
  - ```npm run dev```
  - ```npm run prod```
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
