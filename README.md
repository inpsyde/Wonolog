
Wonolog 
====================

![Wonolog](assets/images/banner.png)



> *Monolog-based logger package for WordPress*



# Table of contents







# Introduction

Wonolog is a Composer package (not a plugin) that allows to log anything "happen" in WordPress.

It is based on [Monolog](https://github.com/Seldaek/monolog) that, with its over 38 millions of downloads and thousands of dependant packages, is the most popular logging library for PHP, compatible with PSR-3 standard.



# Minimum requirements and dependencies

Wonolog requires:

- PHP 5.5+
- WordPress 4.6+
- Composer to be installed

Via Composer, Wonolog requires "monolog/monolog" (MIT).

When installed for development, via Composer, Wonolog also requires:

- "phpunit/phpunit" (BSD-3-Clause)
- "brain/monkey" (MIT)
- "gmazzap/andrew" (MIT)
- "mikey179/vfsStream": (BSD-3-Clause)



# Getting started

Wonolog should be installed via Composer. Its package name is `inpsyde/wonolog`.

**The suggested way to use Wonolog is at website level**.

If you don't use Composer to manage you whole website then Wonolog is probably not for you. You *could* use it, but supported is not warranted.

Wonolog makes possible to develop plugins and themes being compatible with Wonolog logging, without declaring it as a dependency. 

A couple of noteworthy things:

- all Wonolog configurations have to be done in a MU plugin
- all Wonolog configurations are _naturally_ site-wide in a network install

On the bright side, Wonolog comes with super easy bootstrap routine and some out-of-the-box configurations that make it possible to have a working and effective logging system with zero effort.

To get started with defaults settings it is needed:

1. install Wonolog via Composer
2. ensure Composer autoload is loaded in `wp-config.php` or anytime before `'muplugins_loaded'` hook is fired
3. create a **mu-plugin** that, at least, contains this code:

```php
<?php
Inpsyde\Wonolog\bootstrap();
```



## Wonolog defaults

The three steps described above are all is necessary to have a working logging system that uses a Monolog to write logs in a file whose path changes based on current date, using the format: `{WP_CONTENT_DIR}/wonolog/{Y/m/d}.log`, where `{Y/m/d}` is actually replaced by `date('Y/m/d')` so, for example, `/wp-content/2017/02/27.log`.

What is actually logged depends on the value of `WP_DEBUG_LOG` constant.

When `WP_DEBUG_LOG` is true, Wonolog will log everything, but when it is false it will only log events with a log level higher or equal to `ERROR` according to [PSR-3 log levels](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md#5-psrlogloglevel)

"Automatically" logged events includes:

- PHP core error / warnings / fatal errors
- Uncaught exceptions
- WordPress errors and events (DB errors, HTTP API errors, `wp_mail()` errors..., 404 errors...)

**This is just the default behavior**.

The `bootstrap()` function provides entry point for many configurations and customizations.

Moreover the packages provides filters, actions, and configuration via environment variables that makes Wonolog _very_ flexible and expose all the power that Monolog provides.

Before going through all the possibilities offered by Wonolog it is necessary to make clear some basic concepts that will be used in the rest of documentation.



# Monolog concepts

Wonolog is a sort of "bridge" between Monolog and WordPress.

To get the best out of Wonolog, the understanding of some Monolog "basics" are required.



## Loggers and handlers

The main objects in Monolog are "loggers". 

Every logger as a "channel" and one or more "handlers".

The channel is just a name for the Logger, and let's you identify the _kind_ of event the logger handles.

For example, you may have logger for security logs, with a channel name of "Security" and a logger for database errors with a channel name of "Database" and so on.

Handlers are objects that actually "write" the log "somewhere".

The awesome about Monolog is that it comes with [a lot of ready made handlers](https://github.com/Seldaek/monolog/blob/master/doc/02-handlers-formatters-processors.md#handlers) that covers a lot of use cases.

There are handlers that write logs to files, to generic streams, to emails, third party services...

Please refer to [Monolog documentation](https://github.com/Seldaek/monolog/blob/master/doc/02-handlers-formatters-processors.md) for more information and for the list of supported handlers.

As will be better explained in [Wonolog customization](#wonolog-customization) section below, Wonolog offers ways to expose Monolog
logger objects, so it is very easy to make use of all of ready made or even custom handlers.

Every Monolog handler comes with:

- a minimum "log level"
- one or more "log processors"



## Log processors

In Monolog every log event has a "raw" representation, that takes the form of an array.

It contains log event basic data (message, channel, level, context, date and time). May be desirable to customize what a record contain. This customization can be done in a programmatic way using "processors".

A processor is no more than a callback that receives the log record array, processes it and returns the processed log record.

Processor can be added at logger level (all the records in a channel will be processed) or at handler level (all the records processed by a specific handler will be processed).

For example, you may want to add some context to all the logs of a channel, but strip sensitive data from log record  sent to third party services.

Please refer to [Monolog documentation](https://github.com/Seldaek/monolog/blob/master/doc/01-usage.md) for more info on processors.

Wonolog ships with a default log processor that is applied by default to all the log records. 

This processor add to the record some context regarding WordPress status when the record was created. 

It will, in fact, add information about the kind of request (admin, AJAX, REST or XML_RPC), if multisite or not (and when yes, the current site and other multisite-specific context) and when possible the ID of current logged in user.

Just like any other Wonolog feature this default processor can be customized or even disabled. Learn how in the [Wonolog customization](#wonolog-customization) section.



## Log levels

In WordPress there's a "binary" setting for logging: `WP_DEBUG_LOG`. It is a constant that can be either `true` or `false`, 
so the log can be turned on or off.

Monolog (and thus Wonolog) supports different "levels" of logging and **each handler can be set with a minimum level**, independently from other handlers.

For example, there might be an email handler that sends a message when an "emergency" error happen, but does nothing  when a less critical event happen, while at same time a file handler write any message to a log file, no matter the level.

Monolog log levels are inherited from [PSR-3 standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md#5-psrlogloglevel), and can conveniently be accessed as class constants of `Monolog\Logger` class.

They are (in descending order of severity):

- `Logger::EMERGENCY`
- `Logger::ALERT`
- `Logger::CRITICAL`
- `Logger::ERROR`
- `Logger::WARNING`
- `Logger::NOTICE`
- `Logger::INFO`
- `Logger::DEBUG`



# Logging with Wonolog

One of the aim of Wonolog is to make plugins and themes to be compatible with it, without requiring it as a dependency.

For this reason, logging in Wonolog is done via a WordPress function: `do_action()`.

The main hook to use for the scope is **`wonolog.log`**.

For example a bare minimum example of logging with Wonolog could be:

```php
do_action( 'wonolog.log', 'Something happen.' );
```

This is nice and easy, however there are a few thing missing, the most important are:

- the "channel" of the log event
- the "level" of the log event

It still works because Wonolog will _kindly_ set them with defaults, but in real world is better to have control on this.



## Log events data

In Monolog (and Wonolog) every log record comes with some information:

| Data key     | Type    | Description                              |
| ------------ | ------- | ---------------------------------------- |
| `"message"`  | string  | Textual description of the log event     |
| `"channel"`° | string  | The name of the logger to use. Every logger will handle the log differently |
| `"level"`°°  | integer | "Severity" of the event.                 |
| `"context"`  | array   | Arbitrary data that gives context to the event. |

° *There are no default channels in Monolog, but there are some default channels in Wonolog. They are accessed via class constants of a `Channels` class. More on this below.*

°° *Log level is normally represented with an integer, that can be compared in order of severity with the levels defined by PSR-3, which assign to each of the eight log levels a name (as interface constant) and an integer (as that constant value).*

An example of logging an array of data with Wonolog could be:

```php
do_action(
	'wonolog.log',
	[
		'message' => 'Something happen.',
		'channel' => 'DEBUG',
		'level'   => 100,
		'context' => [],
	]
);
```

By using arrays as format to contain log data and `do_action` to trigger the log event, Wonolog allows plugins and 
themes to be compatible with Wonolog without requiring it as a dependency: if such code is ran when Wonolog is not available, nothing bad happen.

Moreover, at any point it would be possible to hook the log event with some other logging package and be able to log data in a different way without changing any code.



### Logging data via Wonolog objects

Even if arrays are a good way to make code to be logged not dependant from Wonolog, there are cases when we write code that is dependant on Wonolog _on purpose_ (e.g. when we write some Wonolog extensions). 

In those cases, it's important to know that internally Wonolog handles log data with objects implementing 
`Inpsyde\Wonolog\Data\LogDatainterface`.

There are a few of them. The most simple is `Inpsyde\Wonolog\Data\Log` whose constructor signature is:

```php
public function __construct(
  $message = '',
  $level = Logger::DEBUG,
  $channel = Channels::DEBUG,
  array $context = []
)
```

By using this object, a log event that does the same of the array example above does is:

```php
do_action( 'wonolog.log', new Data\Log( 'Something happen.', Logger::CRITICAL ) );
```

Wonolog also ships with a series of objects that represent specific error levels, making the triggering of events 
less verbose. All the objects are in the `Inpsyde\Wonolog\Data` namespace and there's an object for each log level.

The constructor of those objects is the same of `Inpsyde\Wonolog\Data\Log` except that they don't take any `$level` as it is specific per object.

For example, the same result of the example above could be obtained with:

```php
do_action( 'wonolog.log', new Data\Critical( 'Something happen.' ) );
```



### Logging events from `WP_Error`

When dealing with WordPress code it's easy to encounter functions / methods that return `WP_Error` instances 
when something goes wrong.

To make integration with such code easier, Wonolog supports log record data to be passed via `WP_Error` objects.

The log message will be taken from `WP_Error` (via `WP_Error::get_error_message()`) as well as log context 
(via `WP_Error::get_error_data()`).

Regarding channel and level, they could be passed explicitly or Wonolog will try to guess them from the error code.

This "guessing" works well with `WP_Error` instances returned by core, could not work well with custom error objects.

For example, assuming a `WP_Error` like this:

```php
global $wpdb;
$wp_error = new \WP_Error( $wpdb->last_error, 'wpdb_error', ['query' => $wpdb->last_query ] );
```

it is possible to do:

```php
do_action( 'wonolog.log', $wp_error );
```

And Wonolog will be recognize the message, the context, the channel (`Channels::DB`) and will set the
level to `Logger::ERROR`.

The level can also be set explicitly sending a second parameter with `do_action`, like:

```php
do_action( 'wonolog.log', $wp_error, Logger::CRITICAL );
```

and channel can be be set explicitly sending a third parameter with `do_action`, like:

```php
do_action( 'wonolog.log', $wp_error, Logger::WARNING, Channels::DEBUG );
```



### Logging events from exceptions

Another common use case is to log something when an exception is thrown during execution of code.

Worth nothing, that uncaught exceptions will be logged by Wonolog automatically (by default) but, hopefully, your code catches any thrown exceptions and you may want to log them.

An example usage could be:

```php
try {
	...do somethig here
      
} catch( \Exception $exception ) {

	// Log exception
	do_action( 'wonolog.log', $exception );
	
	// when debug is on, we want to see this popping up
	if ( defined('WP_DEBUG') && WP_DEBUG ) {
		throw $e;
	}
	
	// Debug is off, silence is golden in production...
}
```

Note that Wonolog works well with PHP 7+ `\Throwable`.

When the only argument passed to `wonolog.log` hook is the exception instance, the level of the log event is assumed to be `LogLevel::ERROR`, and the channel `Channels::DEBUG`, but just like when logging `WP_Error` it is possible to explicitly pass error level and error channel:

```php
do_action( 'wonolog.log', $exception, Logger::CRITICAL, Channels::DB );
```




## Level-rich log hooks

As of now, we seen only one Wonolog log hook, **`'wonolog.log'`**.

However, there are more hooks that can be used to log data.

These are hooks **who embeds error level** in them.

For example, the following log code:

```php
do_action( 'wonolog.log', [ 'message' => 'Please log me!', 'level' => 'INFO' ] );
```

can be also rewritten like so:

```php
do_action( 'wonolog.log.info', [ 'message' => 'Please log me!' ] );
```

This could make logging actions calls more concise, even without relying in Wonolog objects (and thus not making code using these hooks dependant on Wonolog).

There is one hook per each level, so we have:

- `'wonolog.log.emergency'`
- `'wonolog.log.alert'`
- `'wonolog.log.critical'`
- `'wonolog.log.error'`
- `'wonolog.log.warning'`
- `'wonolog.log.notice'`
- `'wonolog.log.info'`
- `'wonolog.log.debug'`

In case one of the above hook is used passing some data that *also* contains hook level information, the level with  higher severity *wins*.

For example:

```php
// In this case the logged level will be "ERROR", because it has higher severity than "DEBUG"
do_action( 'wonolog.log.debug', [ 'message' => 'Please log me!', 'level' => 'ERROR' ] );

// In this case the logged level will be "CRITICAL", because it has higher severity than "ERROR"
do_action( 'wonolog.log.critical', [ 'message' => 'Please log me!', 'level' => 'ERROR' ] );
```

The same applies if the data is done with Wonolog log data objects:

```php
// In this case the logged level will be "ERROR", because it has higher severity than "DEBUG"
do_action( 'wonolog.log.error', new Debug( 'message' => 'Please log me!' ));

// In this case the logged level will be "CRITICAL", because it has higher severity than "ERROR"
do_action( 'wonolog.log.critical', new Log( 'Please log me!', Logger::ERROR ) );
```



# Knowing Wonolog better

Monolog is an awesome piece of software also thanks to its flexibility. However, it needs some "configuration" and "bootstrap code" to work.

Wonolog uses some defaults to provide out-of-the-box functionalities in WordPress context with almost zero effort.

In this sections you'll learn about some assumptions, some defaults and some objects Wonolog uses to do its job.

even if some defaults may sound quite opinionated, most if not all of them are deeply configurable, you'll lean how in the [Wonolog customization](#wonolog-customization) section.



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

It worth to be reminded that such customization **must be done in a MU plugin** to be reliable, because Wonolog bootstrap itself at `'muplugins_loaded'` hook and any customization that happens after bootstrap will not work (or not work as expected).



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

In the section [Wonolog customization](#wonolog-customization) it is explained how to disable this PHP error handler.



## Default handler minimum log level

An important setting of any handler in Monolog is the **"minimum level"** for default handler.

As explained in the "Monolog concepts" section above, each handler may have a different minimum level.

**Any log record that has a severity lower than handler minimum level will be just ignored by the handler.**

As explained in the "Getting started"  section, when no further configuration is provided, Wonolog uses a default handler, and its minimum level is set (by default) according to value of `WP_DEBUG_LOG` constant.

Notes that the **default handler minimum level may be the primary cause of a log events be logged or not by Wonolog**.

There are different ways to customize its value.

Besides of defining `WP_DEBUG_LOG` which don't really allow fine grained customization, there's another way to configure minimum level used by Wonolog default handler.

Considering that minimum debug level is highly connected to environment (production/staging/development...) Wonolog supports an **environment variable that when defined takes precedence over constants settings**.

The environment variable name is **`'WONOLOG_DEFAULT_MIN_LEVEL'`**, it's value can be an integer (which will be straight 
used as minimum level) or a name of a severity level defined by PSR-3.

For example:

```php
putenv("WONOLOG_DEFAULT_MIN_LEVEL=CRITICAL");
// or equivalent
putenv("WONOLOG_DEFAULT_MIN_LEVEL=500");
```

As you'll learn in [Wonolog customization](#wonolog-customization) section below, Wonolog allows to replace its default handler with a custom one.

When using a custom handler, its minimum level it's up to you and the the value of `WP_DEBUG_LOG` or the `'WONOLOG_DEFAULT_MIN_LEVEL'` environment variable will have no effect on it.



## The "logging dilemma"

A general concern with logging is that more often than not the simplest way to perform log is to put logging code side by side with code that does the business logic.

However, this not sounds as a good idea. Logging is something that heavily depends on the infrastructure and business logic concerns should not be mixed with infrastructure concerns. Moreover,  we easily break the "single responsibility principle" putting logging code inside objects that have very different responsibility.

An approach to solve this issue is [AOC](https://en.wikipedia.org/wiki/Aspect-oriented_programming), which is possible in PHP, but surely not "easy" or convenient".

However in WordPress, most of the things happen (in both core and plugin/themes) via hooks.

From "logging perspective" this is very nice because leveraging "events" is another other very common solution for the _"logging dilemma"_.

In fact, thanks to hooks, we can do something like:

```php
add_action( 'some_hook', function() {
	do_action(
		'wonolog.log',
		new Log( current_filter().' action fired', Logger::DEBUG, Channels::DEBUG, func_get_args() )
	);
});
```

Considering that pretty much everything in WordPress is done via hooks, it means that pretty much everything in WordPress can be logged.

Leveraging hooks for logging is exactly what Wonolog does and suggests to do. 

As pretty much anything in Wonolog, this is done by specialized objects, called "**hook listeners**".



## Introducing hook listeners

When Wonolog is installed, **without any further configuration it starts logging some events that happen in the WordPress website**.

Some of these "events" will be PHP errors (as seen above) but some other are events specific to WordPress.

The way Wonolog do this is not rocket science: it add some callbacks to actions and filters triggered by WordPress and according to the hook fired and the arguments associated to it, some log record may be added.

This task is done by specialized objects, called "hook listeners".

Technically speaking, an hook listener is an object implementing `Inpsyde\Wonolog\HookListener\HookListenerInterface`.

Conceptually, an hook listener is an object that "listen to" one or more hooks to be triggered and based on some internal logic, returns object implementing of `LogDataInterface` (See ["Log events data"](#log-events-data) section).

When this happen, the returned objects are logged.



## Shipped hook listeners

Wonolog ships with few "hook listeners" used to log "events" that happen in core.

All of them are in the `Inpsyde\Wonolog\HookListener` namespace and they are:

- `CronDebugListener`
- `HttpApiListener`
- `MailerListener`
- `DbErrorListener`
- `WpDieHandlerListener`
- `QueryErrorsListener`
- `FailedLoginListener`

Every listener is specialized in producing logs for a specific WordPress core "area" or "API". Looking at their names should be possible to guess which it is. Who want to know more, [looking at code](https://github.com/inpsyde/wonolog/tree/master/src/HookListener) is best option.

Of course, it is possible to write custom hook listeners, and actually that's the suggested way to log records using  Wonolog without coupling the code with it. 

In the [Wonolog customization](#wonolog-customization) section below, we will also see how to write and implement custom hook listeners.



# Wonolog customization

## The `'wonolog.setup'` hook



## Programmatically disable Wonolog



## Bootstrap configuration

### Replace default handler

### Bootstrap flags



## The `Controller` API

### Customize default handler

### Customize default processor

### Customize default hook listeners

### Use custom handlers

### Use custom processors

### Use custom hook listeners



## Handlers registry and processors registry

### Configure specific loggers via hook

### Configure specific  handlers via hook



# License

Copyright (c) 2016 Inpsyde GmbH.

Wonolog code is licensed under [MIT license](https://opensource.org/licenses/MIT).

The team at [Inpsyde](https://inpsyde.com) is engineering the Web since 2006.
