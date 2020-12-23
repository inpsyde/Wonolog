# A Deeper Look at Wonolog

## Table of Contents

- [Wonolog Channels](#wonolog-channels)
- [Wonolog PHP Error Handler](#wonolog-php-error-handler)
- [Default Handler Minimum Log Level](#default-handler-minimum-log-level)
- [The "Logging Dilemma"](#the-logging-dilemma)


## Wonolog Channels

When installed, Wonolog comes with five default channels, mainly intended to log things that happen within WordPress core.

Available as class constants of the `Channels` class, the channels are:

- `Channels::DB`
- `Channels::DEBUG`
- `Channels::HTTP`
- `Channels::PHP_ERROR`
- `Channels::SECURITY`

Three of these are for specific areas of WordPress core code: "HTTP" (that includes HTTP in general, but also REST API), "DB" for database, and "SECURITY" (for authorization and other security-related events).

"PHP_ERROR" is a channel that is used to log PHP errors (i.e., notices, warnings, or even fatal errors and uncaught exceptions) that might be triggered by the application.

Lastly, "DEBUG" is a generic channel for events that don't belong to any of the other channels.
This channel is used as default when no channel is explicitly set via log data.

Wonolog channels can be customized via a filter hook, **`'wonolog.channels'`**, that passes an array of currently available channels, and can be used to add custom channels or remove default ones.

```php
add_filter( 'wonolog.channels', function( array $channels ) {

    // Remove a default channel.
    unset( $channels[ Channels::DEBUG ] );

    // Add a custom channel.
    $channels[] = 'my_plugin';

    return $channels;
} );
```

Please keep in mind that such customization **must be done in an MU plugin**, because Wonolog's bootstrapping assumes any configuration is done before the `'muplugins_loaded'` action, thus any customization that happens afterwards is not assured to work.


## Wonolog PHP Error Handler

As mentioned before, by default, Wonolog logs all kinds of PHP errors. It does not log silenced PHP errors.

This is possible because Wonolog registers custom error and exception handlers.

The **log channel** used for these events is `Channels::PHP_ERROR`, and the **log level** is mapped from PHP error constants, with a "map" that looks like this:

```php
// PHP error constant => Monolog log severity
[
    E_COMPILE_ERROR     => Logger::CRITICAL,
    E_COMPILE_WARNING   => Logger::CRITICAL,
    E_CORE_ERROR        => Logger::CRITICAL,
    E_CORE_WARNING      => Logger::CRITICAL,
    E_DEPRECATED        => Logger::NOTICE,
    E_ERROR             => Logger::CRITICAL,
    E_NOTICE            => Logger::NOTICE,
    E_PARSE             => Logger::CRITICAL,
    E_RECOVERABLE_ERROR => Logger::ERROR,
    E_STRICT            => Logger::NOTICE,
    E_USER_DEPRECATED   => Logger::NOTICE,
    E_USER_ERROR        => Logger::ERROR,
    E_USER_NOTICE       => Logger::NOTICE,
    E_USER_WARNING      => Logger::WARNING,
    E_WARNING           => Logger::WARNING,
];
```

Refer to [Wonolog Customization](05-wonolog-customization.md) to learn how to customize or even disable this PHP error handler.

If you want to log also silenced PHP errors you can do so with a filter:
```
add_filter('wonolog.report-silenced-errors', '__return_true');
```

## Default Handler Minimum Log Level

An important setting of any handler in Monolog is its **minimum log level**.

Please keep in mind that each handler may have a different minimum level and **any log record that has a severity lower than handler minimum level will be ignored by the handler**.

When no further configuration is provided, Wonolog uses a default handler, and its minimum level is set according to the value of the `WP_DEBUG_LOG` constant.

Please note that, with out-of-the-box configuration, the assumed **default handler minimum level may be the primary cause of log records to not be logged by Wonolog**.

There are different ways to customize default handler minimum level.

Considering that minimum debug level is highly connected to environment (e.g., development, staging, or production), Wonolog supports an **environment variable that, when defined, takes precedence over constant settings**.

The environment variable is **`'WONOLOG_DEFAULT_MIN_LEVEL'`**, and its value can be an integer (which will be straight used as minimum level) or a name of a severity level defined by PSR-3.

An example:

```php
putenv( 'WONOLOG_DEFAULT_MIN_LEVEL=CRITICAL' );

// Or, equivalently:

putenv( 'WONOLOG_DEFAULT_MIN_LEVEL=500' );
```

Refer to [Wonolog Customization](05-wonolog-customization.md), to see how Wonolog allows to replace its default handler with a custom one.

When a custom handler is in use, its minimum level is out of control for Wonolog, and so the `WP_DEBUG_LOG` or the `'WONOLOG_DEFAULT_MIN_LEVEL'` environment variable will have no effect on it.


## The "Logging Dilemma"

A general concern with logging is that the simplest way to log any data is to put logging code side by side with code that does the business logic.

However, this does not sound like a good idea.
Logging is something that heavily depends on both the environment and infrastructure, and business logic concerns should not be mixed with environment and infrastructure concerns.
Moreover, we easily break the "Single Responsibility Principle" putting logging code inside objects that have very different responsibility.

One approach to solve this issue is [Aspect-oriented Programming (AOC)](https://en.wikipedia.org/wiki/Aspect-oriented_programming), which is _possible_ in PHP, but surely not easy or convenient.

However, in WordPress, most of the things happen (in core as well as in plugins or themes) via hooks.

From a "logging perspective", this is very nice because leveraging "events" is another very common solution for the _"logging dilemma"_.

In fact, thanks to hooks, we can do something like this:

```php
add_action( 'some_hook', function() {

    do_action( 'wonolog.log', new Log(
        current_filter() . ' action fired.',
        Logger::DEBUG,
        Channels::DEBUG,
        func_get_args()
    ) );
} );
```

Considering that pretty much everything in WordPress is done via hooks, this means that pretty much everything in WordPress can be logged.

Leveraging hooks for logging is exactly what Wonolog does and suggests to do.

As pretty much anything in Wonolog, this is done by specialized objects, called [**Hook Listeners**](04-hook-listeners.md).


----

Read next:

- [04 - Hook Listeners](04-hook-listeners.md) to read about hook listeners, the powerful feature of Wonolog that allows for logging any WordPress code.
- [05 - Wonolog Customization](05-wonolog-customization.md) for a deep travel through all the possible configurations available for any aspect of the package.
- [06 - Custom Hook Listeners](06-custom-hook-listeners.md) to see a complete example of a custom hook listener, its integration in Wonolog, and all the things that you need to know in order to write reusable Wonolog extensions.

Read previous: 

- [02 - Basic Wonolog Concepts](02-basic-wonolog-concepts.md) to learn the basics of logging with Wonolog.
- [01 - Monolog Primer](01-monolog-primer.md) to learn a bit more about Monolog core concepts.

-------

[< Back to Index](https://github.com/inpsyde/Wonolog/)
