# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2026.02.01] - 2026-02-01
### Fixed
- Fixed Instagram post failure email to use event slug instead of ID in URLs for better accessibility and identification ([#1648](https://github.com/geoff-maddock/events-tracker/pull/1648))


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

[Unreleased]: https://github.com/geoff-maddock/events-tracker/compare/v2026.02.01...HEAD
[2026.02.01]: https://github.com/geoff-maddock/events-tracker/compare/v2025.01.01...v2026.02.01
[0.1.0]: https://github.com/geoff-maddock/events-tracker/releases/tag/v0.1.0

