# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Click-tracking redirect layer for Event and Series ticket links
  - New `/go/evt-{id}` and `/go/ser-{id}` routes that track clicks before redirecting to ticket URLs
  - Tracks event_id, venue_id, promoter_id, tags, user_agent, referrer, IP address, and timestamp
  - Automatically attaches referral parameters to ticket URLs
  - Comprehensive test coverage for click tracking functionality

### Fixed
- Fixed bug in email templates where ticket links incorrectly used `$link` variable instead of `$ticket`


## [0.1.0] - 2017-08-03
### Added
- All base functionality for event repository - pieces that have been in development for a while, but not released.
- Laravel v5.3 framework base
- Events - shows and club nights object - CRUD, listings, calendar, notifications
- Entities - individuals, groups, locations that are artists, venues, etc - CRUD, listings
- Series - shows and club nights that repeat on a schedule - CRUD, listings, calendar
- Tags - keywords that describe objects
- Forum - message board feature with threads and posts 
- Search - search function takes keyword and returns matching events, series, tags, users
- Configuration files that make use of environment vars for population
- Added CHANGELOG file to track the changes as this project evolves.

### Changed
### Removed

[Unreleased]: https://github.com/geoff-maddock/events-tracker/compare/v0.1.0...HEAD

