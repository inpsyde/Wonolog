# Wonolog 

![Wonolog](assets/images/banner.png)

> *Monolog-based logger package for WordPress*

------

## Table of Contents

- [Introduction](#introduction)
- [Minimum requirements and dependencies](#minimum-requirements-and-dependencies)
- [Getting started](#getting-started)
- [Wonolog defaults](#wonolog-defaults)
- [Learn more](#learn-more)
- [License and copyright](#license-and-copyright)

------

## Introduction

Wonolog is a Composer package (not a plugin) that allows to log anything "happen" in WordPress.

It is based on [Monolog](https://github.com/Seldaek/monolog) that, with its over 38 millions of downloads and thousands 
of dependant packages, is the most popular logging library for PHP, compatible with PSR-3 standard.


## Minimum requirements and dependencies

Wonolog requires:

- PHP 5.5+
- WordPress 4.6+
- Composer to be installed

Via Composer, Wonolog requires "monolog/monolog" (MIT).

When installed for development, via Composer, Wonolog also requires:

- "phpunit/phpunit" (BSD-3-Clause)
- "brain/monkey" (MIT)
- "mikey179/vfsStream": (BSD-3-Clause)


## Getting started

Wonolog should be installed via Composer. Its package name is `inpsyde/wonolog`.

**The suggested way to use Wonolog is at website level**.

If you don't use Composer to manage your whole website then Wonolog is probably not for you. You *could* use it, but supported 
is not warranted.

Wonolog makes possible to develop plugins and themes being compatible with Wonolog logging, without declaring it as a dependency. 

A couple of noteworthy things:

- all Wonolog configurations have to be done in a MU plugin
- all Wonolog configurations are _naturally_ site-wide in a network install

On the bright side, Wonolog comes with super easy bootstrap routine and some out-of-the-box configurations that make it 
possible to have a working and effective logging system with zero effort.

To get started with defaults settings it is needed:

1. install Wonolog via Composer
2. ensure Composer autoload is loaded in `wp-config.php` or anytime before `'muplugins_loaded'` hook is fired
3. create a **mu-plugin** that, at least, contains this code:

```php
<?php
Inpsyde\Wonolog\bootstrap();
```


## Wonolog defaults

The three steps described above are all is necessary to have a working logging system that uses Monolog to write logs in 
a file whose path changes based on current date, using the format: `{WP_CONTENT_DIR}/wonolog/{Y/m/d}.log`, where `{Y/m/d}` is actually replaced by `date('Y/m/d')`.

For example, a target file could be `/wp-content/2017/02/27.log`.

What is actually logged depends on the value of `WP_DEBUG_LOG` constant.

When `WP_DEBUG_LOG` is true, Wonolog will log everything, but when it is false it will only log events with a log level 
higher or equal to `ERROR` according to [PSR-3 log levels](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md#5-psrlogloglevel)

"Automatically" logged events includes:

- PHP core error / warnings / fatal errors
- Uncaught exceptions
- WordPress errors and events (DB errors, HTTP API errors, `wp_mail()` errors..., 404 errors...)

**This is just the default behavior**.

The `bootstrap()` function provides entry points for many configurations and customizations.

Moreover the packages provides filters, actions, and configuration via environment variables that makes Wonolog _very_ 
flexible and expose all the power that Monolog provides.


## Learn more

Documentation of Wonolog features, defaults, configuration and ways to extends it are documented in separate documentation files.

See:

- [Monolog Primer](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/01-monolog-primer.md) to learn a bit more about
  Monolog core concepts
- [Basic Wonolog concepts](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/02-basic-wonolog-concepts.md) to learn the
  basis of logging with Wonolog
- [A deeper look at Wonolog](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/03-deeper-look-at-wonolog.md) to learn 
  more advanced concepts and features of Wonolog
- [Hook listeners](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/04-hook-listeners.md) to read about "Hook listeners"
  the powerful feature of Wonolog that allows logging any WordPress code
- [Wonolog customization](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/05-wonolog-customization.md) for a deep
  travel through all the possible configurations available for any aspect of the package
- [Custom hook listeners](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/06-custom-hook-listeners.md) to see a complete
  example of a custom hook listener, its integration in Wonolog and all the things that are needed to know to write reusable 
  Wonolog extensions.
  

## License and copyright

Copyright (c) 2016 Inpsyde GmbH.

Wonolog code is licensed under [MIT license](https://opensource.org/licenses/MIT).

The team at [Inpsyde](https://inpsyde.com) is engineering the Web since 2006.
