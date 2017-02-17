Wonolog (by Inpsyde)
====================

> Monolog-based logger package for WordPress.

![Wonolog](assets/images/banner.png)


# TOC

- [Introduction](#introduction)
- [Minimum requirements and dependencies](#minimum-requirements-and-dependencies)
- [Getting started](#getting-started)
  - [Wonolog bootstrap hook](#wonolog-bootstrap-hook)
  - [Disabling Wonolog pragmatically](#disabling-wonolog-pragmatically)
- [Monolog concepts](#monolog-concepts)
  - [Loggers and handlers](#loggers-and-handlers)
  - [Logging levels](#logging-levels)
  - [Handlers processors](#handlers-processors)
- [Logging with Wonolog](#logging-with-wonolog)
  - [Log events data](#log-events-data)
    - [Logging data via Wonolog objects](#logging-data-via-wonolog-objects)
    - [Logging data via array](#logging-data-via-array)
    - [Logging data via `WP_Error`](#logging-data-via-wp_error)
  - [Level-rich log hooks](#level-rich-log-hooks)
- [Wonolog configuration and defaults](#wonolog-configuration-and-defaults)
  - [Wonolog channels](#wonolog-channels)
  - [Wonolog PHP error handler](#wonolog-php-error-handler)
  - [Wonolog default handler](#wonolog-default-handler)
    - [Wonolog default handler minimum level](#wonolog-default-handler-minimum-level)
    - [Wonolog default handler object](#wonolog-default-handler-object)
    - [Change default handler target folder](#change-default-handler-target-folder)
    - [Change default handler target file name format](#change-default-handler-target-file-name-format)
    - [Override default handler target file path](#override-default-handler-target-file-path)
    - [Override or disable default handler for all or some channels](#override-or-disable-default-handler-for-all-or-some-channels)
  - [Wonolog core loggers and listeners](#wonolog-core-loggers-and-listeners)
    - [`CronDebugListener`](#crondebuglistener)
    - [`HttpApiListener`](#httpapilistener)
    - [`MailerListener`](#mailerlistener)
    - [`DbErrorListener`](#dberrorlistener)
    - [`WpDieHandlerListener`](#wpdiehandlerlistener)
    - [`QueryErrorsListener`](#queryerrorslistener)
    - [`FailedLoginListener`](#failedloginlistener)
    - [Disabling hook listeners](#disabling-hook-listeners)
- [Logging third party code with Wonolog](#logging-third-party-code-with-wonolog)
  - [Custom hook listeners](#custom-hook-listeners)
    - [Custom hook listener example](#custom-hook-listener-example)
- [Monolog configuration](#monolog-configuration)
  - [Monolog configuration example](#monolog-configuration-example)
- [License](#license)

----

# Introduction

Wonolog is a Composer package (not a plugin) that allows to log anything "happen" in WordPress.

It is based on [Monolog](https://github.com/Seldaek/monolog) that, with its over 38 millions of downloads and thousands of
 dependant packages, is the most popular logging library for PHP, compatible with PSR-3 standard.
 
--------

# Minimum requirements and dependencies

Wonolog requires:

- PHP 5.5+
- WordPress 4.5.3+
- Composer to be installed

Via Composer, Wonolog requires "monolog/monolog" (MIT).

When installed for development, via Composer, Wonolog also requires:

 - "phpunit/phpunit" (BSD-3-Clause)
 - "brain/monkey" (MIT)
 - "gmazzap/andrew" (MIT)
 - "mikey179/vfsStream": (BSD-3-Clause)
 
--------

# Getting started

Wonolog should be installed via Composer. Its package name is `inpsyde/wonolog`.

When installed via Composer there's **nothing else** to do, beside being sure that Composer autoload is loaded.

**The suggested way to use Wonolog is at website level**, but it could also be used as dependency for a specific plugin / theme.

Moreover, it makes possible to develop plugins and themes being compatible with Wonolog logging, without declaring it as
a dependency. 

More on this below.

When Composer autoload is loaded _before_ WordPress is loaded, very likely the case when the whole website is installed
via Composer, **Wonolog setup itself to be bootstrapped at [`muplugins_loaded`](https://developer.wordpress.org/reference/hooks/muplugins_loaded/)
hook**.

The reason behind this hook choice is that it is the first hook available in WordPress, allowing Wonolog to start logging
 as soon as possible and so be able to log plugin issues.
 
Loading so early means that:
 
 - **all Wonolog configurations have to be done in a MU plugin**
 - all Wonolog configurations are _naturally_ site-wide in a network install 

On the bright side, Wonolog comes with some out-of-the-box configurations that make it possible to have a working and
effective logging system without any configuration at all.

## Wonolog bootstrap hook

Inside a MU plugin, it is possible to use the hook **`'wonolog.setup'`** to _wrap_ all the configuration.

This will ensure that if Wonolog is not available for any reason, nothing inside the configuration callback is done.

In the rest of README different actions and filters will be mentioned and **if not explicitly said differently, all those
actions and filters are assumed to be used in a MU plugin, possibly inside a _wrapper callback_ hooked into `'wonolog.setup'`**.

## Disabling Wonolog programmatically

Wonolog provides a filter to disable it via code.

In fact, returning false to **`'wonolog.enable'`** filter will make Wonolog do nothing.

Example:

```php
add_filter( 'wonolog.enable', '__return_false' );
```

--------

# Monolog concepts

Wonolog is a sort of "bridge" between Monolog and WordPress.

To get the best out of Wonolog, the understanding of some Monolog "basics" are required.

## Loggers and handlers

The main objects in Monolog are "loggers". 

Every logger as a "channel" and one or more "handlers".

The channel is just a name for the logger and it is more a way to group together handlers, that are what actually 
perform the logging.

The awesome about Monolog is that it comes with 
[a lot of ready made handlers](https://github.com/Seldaek/monolog/blob/master/doc/02-handlers-formatters-processors.md#handlers)
that covers a lot of use cases.

There are handlers that write logs to files, to generic streams, to emails, third party services...

Please refer to Monolog documentation for more information and for the list of supported handlers.

Since Wonolog exposes Monolog logger objects via hooks (more on this below), it is very easy to make use of all of ready 
made or even custom handlers.
 
Every Monolog handler comes with:

- a minimum level
- one or more "processors"

## Logging levels

In WordPress there's a "binary" setting for logging, `WP_DEBUG_LOG` is a constant that can be either `true` or `false`, 
so the log can be turned on or off.

Monolog (and thus Wonolog) supports different "levels" of logging and **each handler can be set with a minimum level**,
independently from other handlers.

For example, there might be an email handler that sends a message when an "emergency" error happen, but does nothing
 when a less critical event happen, while at same time a file handler write any message to a log file, no matter
 the level.
  
Monolog levels are inherited from [PSR-3 standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md#5-psrlogloglevel),
and can conveniently be accessed as class constants of `Monolog/Logger` class.

They are (in descending order of severity):

- `Logger::EMERGENCY`
- `Logger::ALERT`
- `Logger::CRITICAL`
- `Logger::ERROR`
- `Logger::WARNING`
- `Logger::NOTICE`
- `Logger::INFO`
- `Logger::DEBUG`

## Handlers processors

In Monolog every log event has a "raw" representation, that takes the form of an array.

However, every handler will need to "process" this array to something that fits its scope.

For example, an handler that writes to a file will need to convert the array to a string.

This "processing" is done via objects, called "processors", and each handler may use one or more of them.

Every ready-made handler ships with a default processor, so by using ready-made handlers there's no need to care about that.

However, it is possible to add more processors to each handler or even write custom handlers with custom processors.

Please refer to [Monolog documentation](https://github.com/Seldaek/monolog/blob/master/doc/01-usage.md) for the how to.

--------

# Logging with Wonolog

One of the aim of Wonolog is to make plugins and themes to be compatible with it, without requiring it as a
dependency.

For this reason, logging in Wonolog is done via a WordPress function: `do_action()`.

The main hook to use for the scope that is **`wonolog.log`**.

For example a minimal example of logging with Wonolog could be:

```php
do_action( 'wonolog.log', 'Something happen.' );
```

This is nice and easy, however there are a few thing missing, the most important are:

 - the "channel" of the log event
 - the "level" of the log event
 
It still works because Wonolog will _kindly_ set them with defaults, but in real world is better to have control on this.

## Log events data

Every log event comes with some information:

| Data key   | Type      | Description |
-------------|-----------|-------------|
| "message"  | string    | Textual description of the log event. |
| "channel"° | string    | Every channel is handled by a logger, which has different handlers, depending on the channel Monolog will choose _how_ the event will be logged. |
| "level"    | integer°° | Severity of the event. Events with low levels might be discarded by some handlers and event with high level might trigger "emergency" handlers. |
| "context"  | array     | Arbitrary data that gives context to the event. For example, an event regarding a post, would probably have the post object as part of the context. |

° _There are no default channels in Monolog, but there are some default channels in Wonolog.
They are accessed via class constants of a `Channels` class. More on this below._
  
°° _Log level is normally represented with an integer, that can be compared in order of severity
with the levels defined by PSR-3, which assign to each of the eight log levels a name (as interface constant) and an 
integer (as that constant value)._

----
  
In Wonolog these information are represented with objects implementing `LogDataIntergface`, but to allow plugins and 
themes to be compatible with Wonolog without requiring it as a dependency, there are other way to set event information
without relying on Wonolog objects and interfaces.

It worth nothing that Monolog always adds to log messages date / time of the log event, so there's no need to add
it to message or context.
 
Moreover, Wonolog, for all logged events that happen after `'init'` hook, will add to log context a string ("yes" or "no") 
indicating if there's an user is logged or not, and the logged user ID, which will always be `0` when no user is logged in. 

### Logging data via Wonolog objects

Wonolog ships with the object `Inpsyde\Wonolog\Data\Log` that generally represent log event data.

Its constructor signature is:

```php
public function __construct(
  $message = '',
  $level = Logger::DEBUG,
  $channel = Channels::CHANNEL_DEBUG,
  array $context = []
)
```

So it allows to set each bit of the log event data and comes with some defaults for convenience.

Using this object, a log event can triggered like this:

```php
do_action( 'wonolog.log', new Data\Log( 'Something happen.', Logger::INFO ) );
```

Wonolog also ships with a series of objects that represent specific error levels, making the triggering of events 
less verbose. All the objects are in the `Inpsyde\Wonolog\Data` namespace and there's an object for each log level.

For example, following line of code has the exact same effect of the the line right above:

```php
do_action( 'wonolog.log', new Data\Info('Something cool happen.') );
```

### Logging data via array

Wonolog objects are an easy and succinct way to represent log events data, however using them in a plugin / theme will make
it dependant on Wonolog. It could be or not not could be ok.

Wonolog supports log events data be passed as array, so that plugin and themes can trigger events without requiring
Wonolog, and if it is not present, events will just be ignored, or maybe handled by some other package / plugin / custom code.

An example:

```php
$log_data = [ 'message' => 'Something happen.', 'level' => 200 ]; // level 200 is "INFO" on Monolog
do_action( 'wonolog.log', $log_data );
```

Note here how an integer is used to represent the log level (avoid usage of Monolog object class constants) to make code
not dependant on Monolog as well, but that's not very readable.

However, Wonolog is "clever" enough to also recognize levels in strings, e.g. the following code will work as well:

```php
$log_data = [ 'message' => 'Something happen.', 'level' => 'INFO' ];
do_action( 'wonolog.log', $log_data );
```

### Logging data via `WP_Error`

When dealing with existing WordPress code (no matter if custom or third party), it's easy to encounter functions / methods
that return `WP_Error` instances when something goes wrong.

To make integration with such code easier, Wonolog supports log events data to be passed via `WP_Error` objects.

The log message will be taken from `WP_Error` (via `WP_Error::get_error_message()`) as well as log context 
(via `WP_Error::get_error_data()`).

Regarding channel and level, they could be passed explicitly or Wonolog will try to guess them from the error code.

This "guessing" works well with `WP_Error` instances returned by core, could not work well with custom error objects.

For example, assuming a `WP_Error` like this:

```php
global $wpdb;
$error = new \WP_Error( $wpdb->last_error, 'wpdb_error', ['query' => $wpdb->last_query ] );
```

it is possible to do:

```php
do_action( 'wonolog.log', $error );
```

And Wonolog will be recognize the message, the context, the channel (`Channels::CHANNEL_DB`) and will set the
level to `Logger::ERROR`.

The level can also be set explicitly sending a second parameter with `do_action`, like:

```php
do_action( 'wonolog.log', $error, Logger::CRITICAL );
```

and channel can be be set explicitly sending a third parameter with `do_action`, like:

```php
do_action( 'wonolog.log', $error, Logger::WARNING, Channels::CHANNEL_DEBUG );
```

In both cases channel is explicitly set or it is "guessed", it can also be filtered via `'wonolog.wp-error-channel'` filter.


## Level-rich log hooks

As of now, we seen only one Wonolog log hook, **`'wonolog.log'`**.

However, there are more hooks that can be used to log data. These are hook who embeds error level in them.

For example, the following log code:

```php
do_action( 'wonolog.log', [ 'message' => 'Please log me!', 'level' => 'INFO' ] );
```

can be also rewritten like so:

```php
do_action( 'wonolog.log.info', [ 'message' => 'Please log me!' ] );
```

This could make logging actions calls more concise, even without relying in Wonolog objects.

There is one hook per each level, so we have:

- `'wonolog.log.emergency'`
- `'wonolog.log.alert'`
- `'wonolog.log.critical'`
- `'wonolog.log.error'`
- `'wonolog.log.warning'`
- `'wonolog.log.notice'`
- `'wonolog.log.info'`
- `'wonolog.log.debug'`

In case one of the above hook is used passing some data that also contain level information, the level with  higher severity wins.

For example:

```php
// In this case the actually logged level will be "ERROR", because it has higher severity than "DEBUG"
do_action( 'wonolog.log.debug', [ 'message' => 'Please log me!', 'level' => 'ERROR' ] );

// In this case the actually logged level will be "CRITICAL", because it has higher severity than "ERROR"
do_action( 'wonolog.log.critical', [ 'message' => 'Please log me!', 'level' => 'ERROR' ] );
```

The same applies if the data is done with Wonolog log data objects:

```php
// In this case the actually logged level will be "ERROR", because it has higher severity than "DEBUG"
do_action( 'wonolog.log.error', new Debug( 'message' => 'Please log me!' ));

// In this case the actually logged level will be "CRITICAL", because it has higher severity than "ERROR"
do_action( 'wonolog.log.critical', new Log( 'Please log me!', Logger::ERROR ) );
```

--------

# Wonolog configuration and defaults

Monolog is an awesome piece of software also thanks to its flexibility. However, it needs some "configuration" and
"bootstrap code" to work.

Wonolog uses some default to make it usable with sensible defaults that also bring out-of-the-box features fine tuned for
WordPress core.

## Wonolog channels

When installed, Wonolog comes with five default "channels", mainly intended to log "events" in WordPress core.

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

The fifth, "PHP_ERROR", is a channel that is used to log PHP error (warning, notices or even fatal errors) that might be
triggered by the application. 

It is possible because Wonolog by default uses [a custom PHP errors handler](http://php.net/manual/en/function.set-error-handler.php)
to intercept those errors and log them. More on this below.

Wonolog channels can be customized via a filter hook: **`'wonolog.channels'`**, that passes an array of currently available
channels, and can be used to add custom channels or remove default ones.

```php
add_filter( 'wonolog.channels', function( array $channels ) {

  // remove a default channel
  unset($channels[Channels::DEBUG]);
  
  // add a custom channel
  $channels[] = 'my_plugin';
  
  return $channels;
} );
```

It worth to be reminded that such customization **must be done in a MU plugin** (possibly using `'wonolog.setup'`) 
to be reliable, because Wonolog bootstrap itself at `'muplugins_loaded'` hook and any customization that happens after 
bootstrap will not work (or not work as expected).

## Wonolog PHP error handler

By default, when it is active Wonolog registers a custom PHP error handler to log any PHP error. A dedicated channel, 
`Channels::PHP_ERROR`, is used to log such errors.

The severity level is mapped from PHP error constants, with a "map" that looks like this:

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

This handler can be disabled returning `false` to the filter **`'wonolog.enable-php-error-handler'`**.

For example, to completely disable it, it is possible to:

```php
add_filter( 'wonolog.enable-php-error-handler', '__return_false' );
```

or maybe, to only enable it on backed, but disabling on frontend:

```php
add_filter( 'wonolog.enable-php-error-handler', 'is_admin' );
```


## Wonolog default handler

Having a channel implies to have a logger for that channel. But a logger alone won't do anything without, at least, one
handler.

This is why Wonolog setup a "default" handler that will be used (by default) for all the channels, even for "custom" 
channels added via `'wonolog.channels'` filter.

### Wonolog default handler minimum level

An important setting of any handler in Monolog is the **"minimum level"**.

As explained in the "Monolog concepts" section above, each handler may have a different minimum level.

Any log event that has a severity lower than handler minimum level will be just ignored by the handler.

This setting is even more important for Wonolog default handler: considering that it is the only handler shipped out of 
the box with Wonolog, the **default handler minimum level may be the primary cause of a log event be logged or not by Wonolog**.

There are different ways to customize its value.

First of all, the "base" minimum level is set to `Logger::ERROR` when `WP_DEBUG_LOG` is set to `false` and is set to
`Logger::DEBUG` (which essentially means "log everything") when `WP_DEBUG_LOG` is set to `true`.

When `WP_DEBUG_LOG` is not defined at all, the level is set referring to `WP_DEBUG` instead of to `WP_DEBUG_LOG`, 
but logic is the same.

Considering that minimum debug level is highly connected to environment (production/staging/development...) Wonolog
supports an **environment variable that when defined takes precedence over constants settings**.

The environment variable name is **`'WONOLOG_DEFAULT_MIN_LEVEL'`**, it's value can be an integer (which will be straight 
used as minimum level) or a name of a severity level defined by PSR-3 and also listed in the _"Logging levels"_ section above.

For example:

```php
putenv("WONOLOG_DEFAULT_MIN_LEVEL=CRITICAL");

// is equivalent to
putenv("WONOLOG_DEFAULT_MIN_LEVEL=500");
```

After the "base" level has been set via environment variable or via constants, it can still be changed from code via the
filter **`'wonolog.default-min-level'`**.


### Wonolog default handler object

Wonolog default handler is a [`StreamHandler`](https://github.com/Seldaek/monolog/blob/master/src/Monolog/Handler/StreamHandler.php),
the handler class that Monolog uses for files.

By default, it is set to write logs on a file, whose path is, by default, `WP_CONTENT . "/wonolog/{$year}/{$month}-{day}.log"`.

There are different customizations available, in fact, it is possible to:

- change target containing folder
- change target file name format
- completely override target file path
- override (or disable) default handler, for all or just some channels

### Change default handler target folder

By default, target folder for default handler is `WP_CONTENT . '/wonolog'`. This folder can be changed in two ways:

 - via the environment variable **`'WONOLOG_HANDLER_FILE_DIR'`**
 - via the filter **`'wonolog.default-handler-folder'`**

 
### Change default handler target file name format

By default, target file name for the default handler is obtained via the PHP code: `date('Y/m-d') . '.log'`.

It means that a subfolder named after current year is created in the target folder, and inside it there will be one file 
per day.
 
By using the filter **`'wonolog.default-handler-name-format'`** it is possible to customize what is passed to `date()` 
to obtain file name.

For example, with:

```php
add_filter( 'wonolog.default-handler-name-format', function() {  
  return 'Y/m/d';
} );
```

Wonolog will create a subfolder named after year, inside it a subfolder for each month, and inside them a file for each day.

Note that is possible to include arbitrary strings and even file extension in the format returned with the filter, because
`date()` will leave those untouched.

However, if no file extension is provided `.log` will be used.


### Override default handler target file path

Wonolog will build the target file using `"{$target_folder}/" . date($target_file_format)`.

Where `$target_folder` and `$target_file_format` are obtained as described above.

The full file path built in like this can be still overridden via **`'wonolog.default-handler-filepath'`** filter.


### Override or disable default handler for all or some channels

Sometimes may be desirable just replace the default handler with another one.

The filter `'wonolog.default-handler'` allows for just that.

There are 2 valid return values for this filter:

- `false`
- anything that implements `Monolog\Handler\HandlerInterface`

By returning `false` no default handler will be used, for any logger, it means that handlers have to be added "manually" 
to loggers, or they will do nothing.

By returning an implementation of `HandlerInterface` that implementation will be used for all loggers.

However, when one decides to disable or replace default handler, they probably want it to be doable for specific loggers,
and not for all, with no context.

This can be done with the filter `"wonolog.default-{$channel}-handler"` where `$channel` is the channel name for the
logger.

This filter allows to set a custom handler specific for the channel. Please note this filter will **not** be triggered if 
something uses `'wonolog.default-handler'` filter returning `false`.

Also note that `"wonolog.default-{$channel}-handler"` can't be used to disable default handler for the channel
(returning anything that not implements `Monolog\Handler\HandlerInterface` will be ignored).

To disable default handler for specific channels, it is available the filter **`'wonolog.use-default-handler'`**.

Callbacks hooking there must return `true` or `false`, but the filter passes the target logger as second argument so it 
is possible to return logger specific values.

For example:

```php
add_filter( 
	'wonolog.use-default-handler',
	function($use, Logger $logger) {  
	  return $logger->getName() === Channels::SECURITY ? false : $use;
	},
	10,
	2
);
```

Code above will return `false` (disabling default handler) when target logger is the one for "security" channel. It will 
have no effect on other loggers.

It worth nothing how code above uses `getName()` method to get logger _channel_.

In fact, in Monolog, the "channel" is just the name of the logger. In Wonolog we refers to "channel" because that term is 
also used in Monolog.

## Wonolog core loggers and listeners

As said above, Wonolog ships with five default channels. For every channel there's a logger that is used to log events
that may happen in the application.

Wonolog also ships with few "listeners". 

A listener is an object implementing `Inpsyde\Wonolog\HookListeners\HookListenerInterface`.

These objects listen to specific events and sends log events.

Shipped listeners are:

- `CronDebugListener`
- `HttpApiListener`
- `MailerListener`
- `DbErrorListener`
- `WpDieHandlerListener`
- `QueryErrorsListener`
- `FailedLoginListener`

### `CronDebugListener`

`CronDebugListener` listens to _each_ WP cron action and add log information about it: time of start / end, duration and context.

All the logs for this listener are placed in the `Channels::DEBUG` channel with `Logger::DEBUG` priority. 

### `HttpApiListener`

`HttpApiListener` listens to [`'http_api_debug'`](https://developer.wordpress.org/reference/hooks/http_api_debug/) hook, 
which pass the response as hook parameter.

If response is erroneous, e.g. it is a `WP_Error` or it contains a `50x` response code, a log event is sent to the
`Channels::HTTP` channel, with `Logger::ERROR` level.

### `MailerListener`

`MailerListener` listens to errors returned by `wp_mail` and logs them in the `Channels::HTTP` channel, with `Logger::ERROR` level.

Moreover, it listen to _each_ email sent via `wp_mail` and logs sending debug events in the `Channels::HTTP` channel, 
with `Logger::DEBUG` level.

This latter logging activity might be prevented by other code that acts on `\PHPMailer`, because this class only supports
a single debug callback that could be overridden by other code.

### `DbErrorListener`

`DbErrorListener` waits for the end of the request ([`shutdown`](https://developer.wordpress.org/reference/hooks/shutdown/) action hook)
and look for errors in the global `$EZSQL_ERROR` variable.

This variable is filled by `wpdb` when some error happened during database operations. If that's the case, the listener
will send a log event to `Channels::DB` channel with `Logger::ERROR` level.

### `WpDieHandlerListener`

`WpDieHandlerListener` listens to any call of `wp_die()` and try to guess which part of application code did trigger it.

In case `wp_die()` was called by `wpdb::bail()` or `wpdb::print_error()`, the listener will send a log event to `Channels::DB` 
channel with `Logger::ERROR` level.

### `QueryErrorsListener`

`QueryErrorsListener` listens to errors in main query, for example 404 errors.

In case of any error, the listener will send a log event to `Channels::HTTP` channel with `Logger::DEBUG` level.

### `FailedLoginListener`

This is the most complex listener. As the name suggests, it listen to failed login attempts.

However, logged events will have *variable level*.

The lister counts the failed login attempts for same user in a given time frame (5 minutes).

During a brute force attack, logging all the failed attempts can be so expensive to put the server down.

For this reason the listener does not log all the failed attempts, but as much faster attempts come, as less frequently 
(and with higher log level) attempts are logged.

The listener logs:

- 3rd attempt and every 20 attempts after that when total attempts are < 100 (23rd, 43rd...) with a level of 
  `Logger::WARNING`
- every 100 when total attempts are > 182 && < 1182 (183rd, 283rd...) with a level of `Logger::ERROR`
- every 200 when total attempts are > 1182 (1183rd, 1383rd...) with a level of `Logger::CRITICAL`

The events are logged in the `Channels::SECURITY` channel.

### Disabling hook listeners

Wonolog provides a filter, **`'wonolog.hook-listener-enabled'`**, that can be used to disable specific listeners.

The filtered value is a boolean, but the filters passes as second param the listener instance.

For example:

```php
add_filter( 
	'wonolog.hook-listener-enabled',
	function($enabled, HookListenerInterface $listener) {  
	  return $listener instanceof FailedLoginListener;
	},
	10,
	2
);
```

The code above only enables `FailedLoginListener`, but disable all the other listeners.

--------

# Logging third party code with Wonolog

A general concern with logging is that is hard to perform logs when the original code is not written to be logged and
we have no access (or we don't want to touch) the original code.

For example, Wonolog assumes that at some point the code triggers `wonolog.log` action to log something.

But most of the times we want to log third party code or we don't want to "pollute" our code with logging code.

An approach to solve this issue is [AOC](https://en.wikipedia.org/wiki/Aspect-oriented_programming) which is possible in
PHP, but surely not "convenient".

However in WordPress, most of the things happen (in both core and plugin/themes) via hooks.

From "logging perspective" this is very nice because actually observer pattern (in substance, "events") is another other 
very common solution for the _"logging dilemma"_.

In fact, thanks to hooks, we can do something like:

```php
add_action( 'some_hook', function() {
	do_action(
		'wonolog.log',
		new Log( current_filter().' action fired', Logger::DEBUG, Channels::DEBUG, func_get_args() )
	);
});
```

or even:

```php
add_filter( 'some_hook', function($value) {
	do_action(
		'wonolog.log',
		new Log( current_filter().' filter applied', Logger::DEBUG, Channels::DEBUG, func_get_args() )
	);
	
	return $value;
});
```

In short, we leverage application hooks to just trigger logging event actions.

Considering that pretty much everything in WordPress is done via hooks, it means that pretty much everything in WordPress
can be logged.

This, for example, is the approach taken by "hook listeners" shipped with Wonolog and can also be used to build custom 
"hook listeners".

## Custom hook listeners

An hook listener is an object implementing `Inpsyde\Wonolog\HookListeners\HookListenerInterface`.

Wonolog is only capable to handle objects that are either instances of `ActionListenerInterface` or `FilterListenerInterface`,
both extending `HookListenerInterface`.

`ActionListenerInterface` has just two methods:

 - **`listen_to()`** which have to return an array of "listened" action hooks 
 - **`update($args)`** that is called when each of the "listened" action hook is fired, receives all the arguments that
    each hook passes as parameters (in the `$args` array) and has to return an instance of`LogDataInterface`.
   
`FilterListenerInterface` has the same two methods plus

- `filter($args)` that is called when each of the "listened" filter hook is applied, it receives all the arguments that 
  each hook passes as parameters (in the `$args` array) and has to return the return value for the filter.
  If one wants just use the listener to perform log and do not affect filter return value, `filter()` method will
  probably just contain:
  
```php
    public function filter($args ) {
        return reset($args);
    }
```

### Custom hook listener example

Let's have a look at an average WordPress plugin code:

```php
class SomePlugin {

  private static $instance;

  public static function instance() {
     if ( ! isset(self::$instance) ) {
        self::$instance = new static();
        self::$instance->init();
        do_action( 'some_plugin_init', $instance );
     }
     
     return self::$instance;
  }
  
  public function init() {
       add_filter( 'some_hook' , [$this, 'on_some_hook'] );
       add_action( 'some_other_hook' , [$this, 'on_some_other_hook'] );
  }
  
  public function on_some_hook($argument) {
      /// some code here
      
      do_action( 'some_plugin_on_some_hook_done' );
      
      return $argument;
  }
  
  public function on_some_other_hook() {
        /// some code here
        
        do_action( 'some_plugin_on_some_other_hook_done' );
    }
}
```

With this code in place we could create a **MU plugin** with following code:

```php
namespace MyWebsite\Listeners;

use Inpsyde\Wonolog\HookListeners;
use Inpsyde\Wonolog\HookListenersRegistry;
use Monolog\Logger;

class SomePluginListener implements HookListeners\ActionListenerInterface {

	public function listen_to() {
	    return [ 
	        'some_plugin_init',
	        'some_plugin_on_some_hook_done',
	        'some_plugin_on_some_other_hook_done'
	    ];
	}
	
	public function update( array $args ) {
		return new Log::from_array([
			'message' => current_filter() . ' just fired',
			'channel' => 'PLUGINS_DEBUG', // This is a custom channel
			'level'   => Logger::DEBUG,
			'context' => $args
    	]);
    }

}

add_action('wonolog.setup', function() {

    // Let's add custom channel
	add_filter( 'wonolog.channels', function(array $channels) {
	   $channels[] = 'PLUGINS_DEBUG';
	   
	   return $channels;
	});
	
	// Let's setup listener above
	add_action( 'wonolog.register-listeners', function(HookListenersRegistry $registry) {
	    $registry->register_listener( new SomePluginListener() );
	});
   
});
```

Beside some concepts already seen in this README, in code above we see a concrete example of a custom hook listener
and how to "register" it in Wonolog via the **`'wonolog.register-listeners'`** hook.

That hook passes an instance of `HookListenersRegistry` that, beside the `register_listener()` method, also has a
`register_listener_factory()` method, which receives a callback that once called will return an hook listener.

For example we could rewrite that part of code above as:

```php	
	// Let's setup listener above
	add_action( 'wonolog.register-listeners', function(HookListenersRegistry $registry) {
	    $registry->register_listener_factory(function() {
	        return new SomePluginListener();
	    });
	});
```

--------

# Monolog configuration
 
As explained in different parts of this README, Wonolog just wraps Monolog and its loggers. However, the _real_ "logging
work" is done via handlers.

Wonolog, out of the box, only handle the "default" handler for all registered channels.

But the real power of Wonolog is in the ability to expose Monolog loggers to be configured with any of the numerous 
[ready-made handlers](https://github.com/Seldaek/monolog/blob/master/doc/02-handlers-formatters-processors.md#handlers) 
for a wide variety of use cases.

Monolog loggers are exposed by Wonolog via the **`wonolog.logger`** action hook that is triggered once for each logger,
just before it is used for the first time.

This hook passes to hooked callback the related instance of `Monolog\Logger` and `Logger::getName()` can be used to 
distinguish one logger from the others.

## Monolog configuration example

Let's assume we want to log events of a specific plugin, with its own channel and handlers.

To make the example more close to real world, let's assume the target plugin is a popular, large plugin: WooCommerce.

The "plan" is:

- Register a custom channel just for WooCommerce
- Replace default handler for this channel with a custom handler, dedicated to it
- Add more handlers to this channel, each with minimum logging level

All of this need to be done in a **MU plugin**.

If we realize this is too much to be done in a single file, we could create a Composer package that would contains 
necessary auto-loaded classes, and maybe a MU plugin that would use that package via Composer.

For the moment, and for simplicity's sake I will just write everything as it was just in a MU plugin file.

Let's start.

```php
namespace MyWebsite\WooCommerce\Logger;

use Inpsyde\Wonolog;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Handler\SlackHandler;
use Monolog\Handler\NativeMailerHandler;

/**
 * Adds custom channel.
 *
 * @param array $channels
 *
 * @return array
 */
function setup_channels( array $channels ) {

	$channels[] = 'woocommerce';

	return $channels;
}

/**
 * Replace default handler for the channel, with an handler very similar to Wonolog default handler
 * but that writes to a Woocommerce specific target file.
 *
 * @return StreamHandler
 */
function setup_default_handler() {

	// Default min level that is configurable via environment vars, constants and filters
	$min_level = ( new Wonolog\LogLevel() )->default_level();

	$path = WP_CONTENT_DIR . '/woocommerce-logs/' . date( 'Y/m' );
	if ( is_multisite() ) {
		$path .= 'sites/' . get_current_blog_id();
	}

	wp_mkdir_p( $path );

	$path .= date( 'd' ) . '.log';

	return new StreamHandler( $path . date( 'd' ) . '.log', $min_level );
}

/**
 * Adds additional handlers to logger.
 *
 * @param Logger $logger
 */
function setup_additional_handlers( Logger $logger ) {

	if ( $logger->getName() !== 'woocommerce' ) {
		return;
	}

	// Syslog handler, minimum level: Logger::NOTICE
	$syslog_handler = new SyslogHandler( 'woocommerce', LOG_USER, Logger::NOTICE );
	$logger->pushHandler( $syslog_handler );

	// Slack handler, minimum level: Logger::WARNING
	// Configs in environment variables
	$slack_token   = getenv( "SLACK_API_TOKEN" );
	$slack_channel = getenv( "SLACK_WC_LOG_CHANNEL" );
	if ( $slack_token && $slack_channel ) {
		$slack_handler = new SlackHandler( $slack_token, $slack_channel, 'WooCommerce', FALSE, NULL, Logger::WARNING );
		$logger->pushHandler( $slack_handler );
	}

	/**
	 * Email handler, minimum level: Logger::CRITICAL.
	 * Configs in environment variables or default to admin email
	 *
	 * @TODO: Write a custom WpMailMailerHandler to wrap wp_mail()
	 */
	$email_to      = getenv( "EMAIL_WC_LOG_EMAIL" ) ? : get_option( 'admin_email' );
	$subject       = 'Critical event from WooCommerce on ' . get_bloginfo( 'name' );
	$from          = 'logger@' . parse_url( home_url(), PHP_URL_HOST );
	$email_handler = new NativeMailerHandler ( $email_to, $subject, $from, Logger::CRITICAL );
	$logger->pushHandler( $email_handler );
}

// As soon as Wonolog is available...
add_action(
	'wonolog.setup', 
	function () {

		add_filter( 'wonolog.channels', __NAMESPACE__ . '\\setup_channels' );
		add_filter( 'wonolog.default-woocommerce-handler', __NAMESPACE__ . '\\setup_default_handler' );
		add_action( 'wonolog.logger', __NAMESPACE__ . '\\setup_additional_handlers' );
	}
);
```

It's done! However... it is logging **nothing**. Because even if everything is setup, there's nothing that is sending
logging events to "woocommerce" channel.

For that purpose, considering that we are not going to add any `do_action('wonolog.log')` to WooCommerce, we we should 
probably write a `WooCommerceActionListener` and a `WooCommerceFilterListener`, extending Wonolog 
`ActionListenerInterface` and `FilterListenerInterface`, respectively.

_This task, however, is left to readers_ :)

--------
 
# License

Copyright (c) 2016 Inpsyde GmbH.

Wonolog code is licensed under [MIT license](https://opensource.org/licenses/MIT).

```
  ___                           _      
 |_ _|_ __  _ __  ___ _   _  __| | ___ 
  | || '_ \| '_ \/ __| | | |/ _` |/ _ \
  | || | | | |_) \__ \ |_| | (_| |  __/
 |___|_| |_| .__/|___/\__, |\__,_|\___|
           |_|        |___/            
```

The team at [Inpsyde](https://inpsyde.com) is engineering the Web since 2006.
