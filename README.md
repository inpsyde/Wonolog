# Wonolog

[![Version](https://img.shields.io/static/v1?label=inpsyde/wonolog&message=v2&color=9FC65D&labelColor=3C3D46&style=for-the-badge)](https://packagist.org/packages/inpsyde/wonolog#2.0.0-beta.1)
[![PHP Version](https://img.shields.io/static/v1?label=php&message=>=7.2&color=9FC65D&labelColor=3C3D46&style=for-the-badge)](https://packagist.org/packages/inpsyde/wonolog#2.0.0-beta.1)
[![Monolog Version](https://img.shields.io/static/v1?label=monolog/monolog&message=^2.3.5&color=9FC65D&labelColor=3C3D46&style=for-the-badge)](https://packagist.org/packages/inpsyde/wonolog#2.0.0-beta.1)
[![Downloads](https://img.shields.io/packagist/dt/inpsyde/wonolog.svg?color=9FC65D&labelColor=3C3D46&style=for-the-badge)](https://packagist.org/packages/inpsyde/wonolog)

### _Monolog-based logging package for WordPress_

[![PHP Static Code Analysis](https://github.com/inpsyde/Wonolog/actions/workflows/php-static-analysis.yml/badge.svg?branch=2.x)](https://github.com/inpsyde/Wonolog/actions/workflows/php-static-analysis.yml)
[![PHP Unit Tests](https://github.com/inpsyde/Wonolog/actions/workflows/php-unit-tests.yml/badge.svg?branch=2.x)](https://github.com/inpsyde/Wonolog/actions/workflows/php-unit-tests.yml)
[![PHP Integration Tests](https://github.com/inpsyde/Wonolog/actions/workflows/php-integration-tests.yml/badge.svg?branch=2.x)](https://github.com/inpsyde/Wonolog/actions/workflows/php-integration-tests.yml)

------

![Wonolog](resources/banner.png)

Wonolog is a Composer package (not a plugin) that allows to log anything that *happens* in a WordPress site.

It is based on [Monolog](https://github.com/Seldaek/monolog), which, with its hundreds millions of downloads ([![Monolog Total Downloads](https://img.shields.io/packagist/dt/monolog/monolog.svg?color=9FC65D&labelColor=3C3D46&style=flat-square)](https://packagist.org/packages/monolog/monolog)) and thousands of dependent packages, is the most popular logging library for PHP, compatible with the [PSR-3 standard](https://www.php-fig.org/psr/psr-3/).

Wonolog v2 uses Monolog v2.



## Minimum requirements and dependencies

Wonolog requires:

- PHP 7.2+
- WordPress 5.9+

Via [Composer](https://getcomposer.org), Wonolog requires `monolog/monolog` (MIT).



## Getting started

Wonolog should be installed via Composer, it's available on [packagist.org package name is `inpsyde/wonolog`](https://packagist.org/packages/inpsyde/wonolog).

**The suggested way to use Wonolog is at website level.**

If you don't use Composer to manage your whole website then Wonolog is probably **not** for you.
You might be able to use it anyway, but support is not guaranteed.

It's easily possible to develop plugins and themes compatible with Wonolog logging even without explicitly declaring it as a dependency.

A couple of noteworthy things:

- all Wonolog configurations have to be done in a MU plugin;
- in a WordPress multisite installation, all Wonolog configurations are _naturally_ site-wide.

On the bright side, Wonolog is plug & play: just install it and an effective logging system is _already_ in place.

To get started with defaults settings, this is required:

1. install Wonolog via Composer;
2. ensure Composer autoload is loaded in `wp-config.php` or anytime before the `'muplugins_loaded'` action is fired;

That's it: no configuration or code is strictly necessary if the default configuration is fine.

## Understanding Wonolog

The idea behind Wonolog is that when developing a plugin, theme, and such, there's absolutely no knowledge about the underline infrastructure that hosts the application (website) in which that plugin/theme/piece-of-code is running.

However, writing log records assumes that knowledge. 

This is why the workflow that Wonolog pursuit is the following:

1. plugins/themes/etc. fire a WordPress action when they want to log something, passing the data to log as action parameters
2. Wonolog listen to that action, and use Monolog to log the information passed as arguments.

In other words, Wonolog is a bridge in between WordPress and Monolog, that allows to use Monolog to write logs for "logging events" emitted by WordPress code, without requiring that WordPress code to be coupled with Wonolog and/or Monolog.



### Monolog in a nutshell

We are not going to document Monolog here, there's already a quite good [documentation](https://seldaek.github.io/monolog/) for it.

Its basic ideas are:

- a *log record* is an array of information, including a "*message*", a "*severity*" (an integer with a 1:1 map with [PSR-3 log levels](https://www.php-fig.org/psr/psr-3/#5-psrlogloglevel)), a "*channel*" (think of it as a "category" for records), and some additional "*context*".
- Each *log record*, before being actually logged, can be changed/extended by one or more *processor*: a PHP callable that takes the log record array as parameter and return the changed/extended array.
- Based on the "*channel*" of the log record, it is assigned to one or more "logger". A *logger* is an object implementing [PSR-3 logger interface](https://www.php-fig.org/psr/psr-3/#3-psrlogloggerinterface) that will take care of logging the record.
- A Monolog *logger* does not actually write log records anywhere. Instead, a Monolog *logger* contains one or more *"handler"*. An *handler* is an object that takes the log record and writes it "somewhere".
    There can be handlers that write log records in files, send them via email or to external services, etc. Monolog ships [a few handlers](https://seldaek.github.io/monolog/doc/02-handlers-formatters-processors.html#handlers) and many more are written by 3rd parties, which means that using Monolog it is possible to write logs in a lot of different ways without having to write custom code.
- When a *logger* contains multiple *handlers*, each of them will handle the record in ordered sequence, one after the other. Each handler in the sequence can decide to stop the sequence by setting its "*bubble*" property to false.
- Each *handler* can be assigned to a "*minimum severity*", so that it will ignore any log record having a severity lower than that. For example, it might be desirable that an email/SMS handler is used only for emergencies and ignores anything with a lower severity.



### Default WordPress log events

Wonolog expects plugins/themes/etc. to fire actions that will be logged.

For example, a plugin could do: `do_action('wonolog.log', 'Something happened')` and Wonolog will pass that message to Monolog that will log the message based on its configuration.

However, in WordPress there are a lot of "events" that are worth to be logged, and we can't expect WordPress to fire `wonolog.log` actions.

This is why Wonolog, out of the box, creates 5 *channels* to be used to log WordPress "events":

- `HTTP`
- `DB`
- `SECURITY`
- `CRON`
- `DEBUG`

What Wonolog does under the hood is to hook default WordPress actions and filters, and based on the arguments passed to those, fire `'wonolog.log'` actions so that those events can be logged.



### Logging PHP errors and exceptions

PHP's exceptions and errors is something that is very likely worth logging.

Wonolog, by default, adds (on top of the 5 channels mentioned above) a channel named `PHP-ERROR`. After that, it adds a [custom exception handler](https://www.php.net/manual/en/function.set-exception-handler.php), and a [custom error handler](https://www.php.net/manual/en/function.set-error-handler.php) that internally call `'wonolog.log'` so that Wonolog is able to log errors and exceptions via Monolog.



### Default handler

It has been said that, for log records to be actually written, Monolog needs one or more *logger* and each *logger* needs one or more *handler*.

Wonolog ships with a default *logger* that is used for all the default channels. This logger has a single handler that write logs in a file whose path is inside WordPress "uploads" folder, in the sub-folder `/wonolog/{$year}/$month}/{$day}.log` (so it changes every day).

This default handler can be disabled at all, customized, extended... just like pretty much any other aspect of the things Wonolog does.

Note that is highly recommended to **don't** write log files to a publicly accessible folder. Wonolog attempts to write an `.htaccess` file in its default logs folder, preventing public access. but depending on server configuration that might not be enough. 



## License and Copyright

Copyright (c) 2023 Inpsyde GmbH. See [LICENSE](LICENSE).

Wonolog code is licensed under GPL v2 or newer license.

The team at [Inpsyde](https://inpsyde.com) is engineering the Web since 2006.
