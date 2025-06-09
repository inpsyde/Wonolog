# Wonolog

[![Version](https://img.shields.io/static/v1?label=inpsyde/wonolog&message=3&color=9FC65D&labelColor=3C3D46&style=for-the-badge)](https://packagist.org/packages/inpsyde/wonolog)
[![PHP Version](https://img.shields.io/static/v1?label=php&message=>=8.1&color=9FC65D&labelColor=3C3D46&style=for-the-badge)](https://packagist.org/packages/inpsyde/wonolog#2.0.0-beta.1)
[![Monolog Version](https://img.shields.io/static/v1?label=monolog/monolog&message=2%20||%203&color=9FC65D&labelColor=3C3D46&style=for-the-badge)](https://packagist.org/packages/inpsyde/wonolog)
[![Downloads](https://img.shields.io/packagist/dt/inpsyde/wonolog.svg?color=9FC65D&labelColor=3C3D46&style=for-the-badge)](https://packagist.org/packages/inpsyde/wonolog)

### _Monolog-based logging package for WordPress_

[![PHP Static Code Analysis](https://github.com/inpsyde/Wonolog/actions/workflows/php-static-analysis.yml/badge.svg)](https://github.com/inpsyde/Wonolog/actions/workflows/php-static-analysis.yml)
[![PHP Unit Tests](https://github.com/inpsyde/Wonolog/actions/workflows/php-unit-tests.yml/badge.svg)](https://github.com/inpsyde/Wonolog/actions/workflows/php-unit-tests.yml)
[![PHP Integration Tests](https://github.com/inpsyde/Wonolog/actions/workflows/php-integration-tests.yml/badge.svg)](https://github.com/inpsyde/Wonolog/actions/workflows/php-integration-tests.yml)

------

![Wonolog](resources/banner.png)

Wonolog is a Composer package (not a plugin) that allows to log anything that *happens* in a WordPress site.

It is based on [Monolog](https://github.com/Seldaek/monolog), which, with its hundreds millions of downloads ([![Monolog Total Downloads](https://img.shields.io/packagist/dt/monolog/monolog.svg?color=9FC65D&labelColor=3C3D46&style=flat-square)](https://packagist.org/packages/monolog/monolog)) and thousands of dependent packages, is the most popular logging library for PHP, compatible with the [PSR-3 standard](https://www.php-fig.org/psr/psr-3/).

Wonolog v3 supports both Monolog v2 and v3.


## Minimum requirements and dependencies

Wonolog requires:

- PHP 8.1+
- WordPress 5.9+

Via [Composer](https://getcomposer.org), Wonolog requires `monolog/monolog` (MIT).

## Documentation

Start [here](https://inpsyde.github.io/Wonolog/).


## Copyright and License

This package is [free software](https://www.gnu.org/philosophy/free-sw.en.html) distributed under the terms of the GNU General Public License version 2 or (at your option) any later version. For the full license, see [LICENSE](./LICENSE).
