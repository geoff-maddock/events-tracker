# Documentation

## Deploment Notes
Follow these steps to configure and deploy the application
0. Verify pre-requisites and set up environment
1. Clone code from Github repository into environment
2. Add required configurations
3. Add optional configurations
4. Configure and secure webserver
5. Build app
6. Open app and login

## Platform / Pre-requisites
* Provision environment that can run Laravel code [unix?]
	 ```
    Digital Ocean Droplet
		Ubuntu LEMP on 16.014
		2vCPUs
		4GB / 80 GB Disk
  ```
* PHP 8,1+ [verify version]
	```
	gmaddock@Wrecked:/var/www/dev-events$ php -v
	PHP 8.1.16 (cli) (built: Feb 14 2023 18:34:05) (NTS)
	```
* Verify required extensions are installed:  pdo_mysql, zip
* MySQL 8 [verify version - may not require 8]
  ```
  gmaddock@Wrecked:/var/www/dev-events$ mysql -V
  mysql  Ver 8.0.18 for Linux on x86_64 (MySQL Community Server - GPL)
  ```
* NodeJS 12.4+
  ```
  gmaddock@Wrecked:/var/www/dev-events$ node -v
  v14.15.5
  ```
* Provision a database and add a user with access
```
        - CREATE DATABASE
        ```
        CREATE DATABASE `stage_events_tracker` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */
        ```
        - CREATE USER
        ```
        CREATE USER 'stage_user'@'%' IDENTIFIED BY 'Momar129!!';
        GRANT ALL ON stage_events_tracker.* TO 'stage_user'@'%';
        FLUSH PRIVILEGES; 
        ```
        - Log in with this user to test
```

## Installing / Getting started
* Connect to the server
* Go to the default path for deployments
```bash
cd /var/www
```
* Clone the repo.
```bash
$ git clone git@github.com:geoff-maddock/events-tracker.git project-name
$ cd project-name
```

* Install the PHP dependencies.
```
$ composer install
```

* Install node dependencies
```
$ npm install
```

## Configuration
* Copy .env.example to .env and add values
  ** Add a database configuration that matches your provisioned database 
  ```
  	APP_ENV=local
    APP_DEBUG=false
    APP_KEY=THIS_VALUE_ADDED_BY_KEY_GENERATION
    APP_URL=https://project-domain.com
    APP_FEEDBACK_EMAIL=your-feedback-email@domain.com
    APP_NOREPLY_EMAIL=noreply@domain.com
    APP_ADMIN_EMAIL=your-admin-email@domain.com
    APP_SUPERUSER=1
    APP_FB_APP_ID=1111111111
    FACEBOOK_APP_ID=999
    FACEBOOK_APP_SECRET=999
    FACEBOOK_GRAPH_VERSION=v5.0

    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=stage_events_tracker
    DB_USERNAME=stage_user
    DB_PASSWORD=database_password
  ```
* Run key generation `php artisan key:generate`
* Run `composer install`
* Run `npm install`
* Run node build for your environment
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
* Add a DNS A record with your authoritative DNS Provider for your domain
* Point web server to /html/index.php
  - Configure NGINX [Recommended]
  - [EXAMPLE]
    ```
        server {

        root /var/www/events-tracker/public;
        index index.php index.html index.htm;

        # Make site accessible from http://localhost/
        server_name events-tracker.com;

        location / {
                # First attempt to serve request as file, then
                # as directory, then fall back to displaying a 404.
                try_files $uri $uri/ /index.php?$args;
                # Uncomment to enable naxsi on this location
                # include /etc/nginx/naxsi.rules
        }

        error_page 404 /404.html;
        error_page 500 502 503 504 /50x.html;
        location = /50x.html {
                root /usr/share/nginx/html;
        }

        location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php7.4-fpm.sock;
        }
    ```
* Set up SSL 
  - Configure Lets Encrypt
    - https://www.digitalocean.com/community/tutorials/how-to-secure-nginx-with-let-s-encrypt-on-ubuntu-18-04
    `sudo certbot --nginx -d your-domain.com`
* Modify permissions
```
	sudo chmod 777 /var/www/project-name/storage/logs/laravel.log
	sudo chgrp -R www-data storage bootstrap/cache
	sudo chmod -R ug+rwx storage bootstrap/cache
```

* Build assets
```bash
npm build prod
```

### Optional Configurations
- Mailgun API key [RECOMMENDED]
- Facebook API key [OPTIONAL]
- Twitter API key  [OPTIONAL]

## Log in to running app
* Visit the domain at https://domain.name
* Log in with the default admin user
  ** admin@yourdomain.com / encodedpassword

# Developers
## CI and Testing

Github PRs are configured with CI pipeline that includes
- PHPStan for static analysis [Level 2]
- MySQL container config to set up database for fixtures
- PHPUnit for tests
- Build process

Run parts of CI manually:

./phpunit tests

## Environments:  Dev, Testing, Production
* Dev environment notes
* Testing environment notes
* Prod environment notes