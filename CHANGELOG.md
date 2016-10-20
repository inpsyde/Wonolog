Changelog
=========

## 0.2.0 (2016-10-20)

### Added

- Introduce support for `wonolog.log.{$level}` hooks
- Introduce new `MailerListener` to log `wp_mail` events

### Changed

- Changed `PhpErrorController` method names to use snake_case.
- Refactoring of bootstrap file, delay of bootstrap routine to priority 20 of "muplugins_loaded"
- Updated README with info on new and changed features

-------

## 0.1.1 (2016-10-20)

### Fixed

- Removed type-hint from `PhpErrorController::onFatal()` because it causes issues with PHP7ù

-------

## 0.1.0 (2016-10-18)

_First release_