# A Deeper look at Wonolog

## Table of contents

- [A deeper look into Wonolog](#a-deeper-look-into-wonolog)
- [Wonolog channels](#wonolog-channels)
- [Wonolog PHP error handler](#wonolog-php-error-handler)
- [Default handler minimum log level](#default-handler-minimum-log-level)
- [The "logging dilemma"](#the-logging-dilemma")


## A deeper look into Wonolog

Monolog is an awesome piece of software also thanks to its flexibility. However, it needs some "configuration" and "bootstrap code" to work.

Wonolog uses some defaults to provide out-of-the-box functionalities in WordPress context with almost zero effort.

In this section you'll learn about some assumptions, some defaults and some objects Wonolog uses to do its job.

Even if some defaults may sound quite opinionated, most (if not all) of them are deeply configurable.

Refers to [Wonolog customization](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/05-wonolog-customization.md) to learn how.



## Wonolog channels

When installed, Wonolog comes with five default "channels", mainly intended to log things that happen in WordPress core.

They are available as class constants of `Channels` class, and they are:

- `Channels::HTTP`
- `Channels::DB`
- `Channels::SECURITY`
- `Channels::DEBUG`
- `Channels::PHP_ERROR`

The first three are for specific "areas" of core code: "HTTP" (that includes HTTP and REST APIs), "DB" for database,
and "SECURITY" (for authorization and other security-related events).

The fourth, "DEBUG", is a generic channel for events that does not belong to any of the other channels. This channel
is used as default when no channel is explicitly set via log data.

The fifth, "PHP_ERROR", is a channel that is used to log PHP errors (warning, notices or even fatal errors and uncaught exceptions) that might be triggered by the application. 

Wonolog channels can be customized via a filter hook: **`'wonolog.channels'`**, that passes an array of currently available channels, and can be used to add custom channels or remove default ones.

```php
add_filter( 'wonolog.channels', function( array $channels ) {

  // remove a default channel
  unset($channels[Channels::DEBUG]);
  
  // add a custom channel
  $channels[] = 'my_plugin';
  
  return $channels;
} );
```

It worth to be reminded that such customization **must be done in a MU plugin**, because Wonolog bootstrap assumes any configuration is done before  `'muplugins_loaded'` hook and any customization that happens after that is not assured to work.



## Wonolog PHP error handler

As already said, by default, Wonolog logs PHP errors (warning, notices or even fatal errors or uncaught exceptions) that might be triggered by the application.

This is possible because Wonolog registers custom error and exceptions handlers.

The  log channel used for this events is `Channels::PHP_ERROR` and the log level is mapped from PHP error constants, with a "map" that looks like this:

```php
// PHP error constant => Monolog log severity
[
	E_USER_ERROR        => Logger::ERROR,
	E_USER_NOTICE       => Logger::NOTICE,
	E_USER_WARNING      => Logger::WARNING,
	E_USER_DEPRECATED   => Logger::NOTICE,
	E_RECOVERABLE_ERROR => Logger::ERROR,
	E_WARNING           => Logger::WARNING,
	E_NOTICE            => Logger::NOTICE,
	E_DEPRECATED        => Logger::NOTICE,
	E_STRICT            => Logger::NOTICE,
	E_ERROR             => Logger::CRITICAL,
	E_PARSE             => Logger::CRITICAL,
	E_CORE_ERROR        => Logger::CRITICAL,
	E_CORE_WARNING      => Logger::CRITICAL,
	E_COMPILE_ERROR     => Logger::CRITICAL,
	E_COMPILE_WARNING   => Logger::CRITICAL,
];
```

Refers to [Wonolog customization](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/05-wonolog-customization.md) to learn 
how to customize or disable this PHP error handler.



## Default handler minimum log level

An important setting of any handler in Monolog is its **"minimum level"**.

It worth to be reminded that each handler may have a different minimum level and **any log record that has a severity lower 
than handler minimum level will be just ignored by the handler.**

When no further configuration is provided, Wonolog uses a default handler, and its minimum level is set (by default) according 
to value of `WP_DEBUG_LOG` constant.

Notes that with out-of-the-box configuration the assumed **default handler minimum level may be the primary cause of log 
records to not be logged by Wonolog**.

There are different ways to customize default handler minimum level.

Considering that minimum debug level is highly connected to environment (production/staging/development...) Wonolog supports 
an **environment variable that when defined takes precedence over constants settings**.

The environment variable name is **`'WONOLOG_DEFAULT_MIN_LEVEL'`**, it's value can be an integer (which will be straight 
used as minimum level) or a name of a severity level defined by PSR-3.

For example:

```php
putenv("WONOLOG_DEFAULT_MIN_LEVEL=CRITICAL");

// or the equivalent

putenv("WONOLOG_DEFAULT_MIN_LEVEL=500");
```

Refers to [Wonolog customization](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/05-wonolog-customization.md), to see 
how Wonolog allows to replace its default handler with a custom one.

When a custom handler is in use, its minimum level is out of Wonolog control, and so the `WP_DEBUG_LOG` or the 
`'WONOLOG_DEFAULT_MIN_LEVEL'` environment variable will have no effect on it.


## The "logging dilemma"

A general concern with logging is that the simplest way to perform log is to put logging code side by side with code that 
does the business logic.

However, this not sounds as a good idea. Logging is something that heavily depends on the environment and on infrastructure 
and business logic concerns should not be mixed with environment and infrastructure concerns. Moreover,  we easily break the 
"single responsibility principle" putting logging code inside objects that have very different responsibility.

An approach to solve this issue is [AOC](https://en.wikipedia.org/wiki/Aspect-oriented_programming), which is possible in 
PHP, but surely not "easy" or "convenient".

However in WordPress, most of the things happen (in both core and plugin/themes) via hooks.

From "logging perspective" this is very nice because leveraging "events" is another other very common solution for the 
_"logging dilemma"_.

In fact, thanks to hooks, we can do something like:

```php
add_action( 'some_hook', function() {
	do_action(
		'wonolog.log',
		new Log( current_filter().' action fired', Logger::DEBUG, Channels::DEBUG, func_get_args() )
	);
});
```

Considering that pretty much everything in WordPress is done via hooks, it means that pretty much everything in WordPress 
can be logged.

Leveraging hooks for logging is exactly what Wonolog does and suggests to do. 

As pretty much anything in Wonolog, this is done by specialized objects, called "**hook listeners**".

Learn more about [hook listeners](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/06-custom-hook-listeners.md).


----

Read next:

- [04 - Hook listeners](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/04-hook-listeners.md)
- [05 - Wonolog customization](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/05-wonolog-customization.md)
- [06 - Custom hook listeners](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/06-custom-hook-listeners.md)

Read previous: 

- [02 - Basic Wonolog concepts](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/02-basic-wonolog-concepts.md)
- [01 - Monolog Primer](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/01-monolog-primer.md)

-------

[< Back to index](https://github.com/inpsyde/wonolog/)