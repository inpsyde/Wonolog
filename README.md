
Wonolog 
====================

![Wonolog](assets/images/banner.png)



> *Monolog-based logger package for WordPress*



# Table of contents



[TOC]



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

The three steps described above are all is necessary to have a working logging system that uses a Monolog to write logs in a file whose path changes based on current date, using the format: `{WP_CONTENT_DIR}/wonolog/{Y/m/d}.log`, where `{Y/m/d}` is actually replaced by `date('Y/m/d')`.

For example, a target file could be `/wp-content/2017/02/27.log`.

What is actually logged depends on the value of `WP_DEBUG_LOG` constant.

When `WP_DEBUG_LOG` is true, Wonolog will log everything, but when it is false it will only log events with a log level higher or equal to `ERROR` according to [PSR-3 log levels](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md#5-psrlogloglevel)

"Automatically" logged events includes:

- PHP core error / warnings / fatal errors
- Uncaught exceptions
- WordPress errors and events (DB errors, HTTP API errors, `wp_mail()` errors..., 404 errors...)



**This is just the default behavior**.



The `bootstrap()` function provides entry points for many configurations and customizations.

Moreover the packages provides filters, actions, and configuration via environment variables that makes Wonolog _very_ flexible and expose all the power that Monolog provides.

Before going through all the possibilities offered by Wonolog it is necessary to make clear some basic concepts that will be used in the rest of documentation.



# Monolog concepts

Wonolog is a sort of "bridge" between Monolog and WordPress.

To get the best out of Wonolog, the understanding of some Monolog "basics" are required.

It is strongly suggested to read the [Monolog documentation about its core concepts](https://github.com/Seldaek/monolog/blob/master/doc/01-usage.md#core-concepts) to get a better understanding of the library.



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

**Wonolog ships with a default log processor that is applied by default to all the log records.**

This processor adds to the record some context regarding WordPress status when the record was created. 

It will, in fact, add information about the kind of request (admin, AJAX, REST or XML_RPC), if multisite or not (and when multisite, the current site id is added besides other multisite-specific context) and when possible the ID of current logged in user.

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

Moreover, at any point it would be possible to hook the log event with some other logging package being able to log data in a different ways without changing any code.



### Logging data via Wonolog objects

Even if arrays are a good way to make code to be logged not dependant from Wonolog, there are cases when is desirable to write code that is dependant on Wonolog _on purpose_ (e.g. when we write some Wonolog extensions). 

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

By using this object, a log event that does the same of the array example above is:

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

Worth nothing that uncaught exceptions will be logged by Wonolog automatically (by default) but, hopefully, your code catches any thrown exceptions and you may want to log them.

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

Even if some defaults may sound quite opinionated, most if not all of them are deeply configurable, you'll lean how in the [Wonolog customization](#wonolog-customization) section.



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

It worth to be reminded that such customization **must be done in a MU plugin** to be reliable, because Wonolog bootstrap assumes any configuration is done before  `'muplugins_loaded'` hook and any customization that happens after that is not assured to work.



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

An important setting of any handler in Monolog is its **"minimum level"**.

As explained in the "Monolog concepts" section above, each handler may have a different minimum level.

**Any log record that has a severity lower than handler minimum level will be just ignored by the handler.**

As explained in the "Getting started"  section, when no further configuration is provided, Wonolog uses a default handler, and its minimum level is set (by default) according to value of `WP_DEBUG_LOG` constant.

Notes that with out-of-the-box configuration the assumed **default handler minimum level may be the primary cause of log events not be logged by Wonolog**.

There are different ways to customize default handler minimum level.

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

The way Wonolog do it is not rocket science: it add some callbacks to actions and filters triggered by WordPress and according to the hook fired and the arguments associated to it, some log records may be added.

This task is done by specialized objects, called "hook listeners".

Technically speaking, an hook listener is an object implementing `Inpsyde\Wonolog\HookListener\HookListenerInterface`.

Conceptually, an hook listener is an object that "listens to" one or more hooks to be triggered and based on some internal logic, returns object implementing of `LogDataInterface` (See ["Log events data"](#log-events-data) section).

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

In the [Wonolog customization](#wonolog-customization) section below, is explained hot to disable some or all the shipped listeners and also how to write and use hook listeners.



# Wonolog customization



### Safe bootstrap

As explained in the "Getting started" section, to be initialized Wonolog needs the function `Inpsyde\Wonolog\bootstrap()` to be called in a MU plugin.

In many cases is safe enough to just do it, because following suggested practice to install Wonolog via Composer at website level, when the mu plugin is ran, Wonolog will always be there.

However, one might want to check for function existence before calling it.

```php
if ( function_exists( 'Inpsyde\Wonolog\bootstrap' ) ) {
  Inpsyde\Wonolog\bootstrap();
}
```

While this works and it's not that bad, the suggested practice is not to check function existence, but check existence of `Inpsyde\Wonolog::LOG` constant instead:

```php
if ( defined( 'Inpsyde\Wonolog\LOG' ) ) {
  Inpsyde\Wonolog\bootstrap();
}

```

That constant is defined in the same file of the bootstrap function, and there's not way constant exists but function doesn't. 

Considering that checking a constant is faster than checking a function (constant is even defined at compile time, allowing for opcode cache optimization) and considering that such code will run on every single request to your website, we suggest this practice as it is a good compromise between safety and performance.



## Programmatically disable Wonolog

When (reasonably) the same code is deployed to all environments it is not possible to enable or disable Wonolog per environment (not being a plugin, no way to _deactivate_ it).

However, it is a quite common practice disable logging on some environments, and this is why Wonolog supports an environment variable that can be used to turn it off completely, it is `WONOLOG_DISABLE`.

For example, by doing:

```php
putenv("WONOLOG_DISABLE=TRUE")
```

Wonolog will do nothing, no matter if `Inpsyde\Wonolog\bootstrap()` is called and how.

Sometimes, may be necessary to enable or disable logging based on other context. In those cases, Wonolog offers a filter, `'wonolog.enable'`, that can be used for the scope:

```php
add_filter( 'wonolog.disable', 'My\App\should_logging_be_disabled' )
```

or

```php
add_filter( 'wonolog.disable', '__return_true' )
```

to always disable Wonolog... but would probably be better to don't call `Inpsyde\Wonolog\bootstrap()` (or comment it out), if that's the desired result.

The filter will receive a single argument that is initially set according to the value of `WONOLOG_DISABLE` environment variable.



## Bootstrap configuration



### Wonolog default handler

When bootstrapping Wonolog with all the defaults the most notably thing that happen is probably the fact that a default handler is attached to the loggers of all channels.

When no handler is added, Monolog attach an handler to all loggers that just dump the content of log record to "standard output".

This choice makes sense for a library like Monolog that left to implementers decide how logs should be handled by default, but being Wonolog a project-specific package (it targets WordPress and nothing else) we thought that to provide out-of-the-box a *persistent* default handler made sense.

As mentioned in the "Getting started section", Wonolog uses as default handler built around Monolog [`StreamHandler`](https://github.com/Seldaek/monolog/blob/master/src/Monolog/Handler/StreamHandler.php) that writes all the logs to file whose path change based on record date.

The default root path for the log files is `{WP_CONTENT_DIR}/wonolog/` and inside that, files are saved to a path formed using the format: `{Y/m/d}.log`.

**Please note: using content folder to store logs is NOT a good idea**.

In fact, logs very likely contain sensitive data that in content folder are publicly accessible and **that is in best case a privacy leakage issue, in worst case a security threat**.

However, WordPress has no place custom code is intended to save files that must not be public. So using a subfolder of content directory is the only option we had to use as default.

Don't worry, everything that regards the default handler is very configurable, in more than one way, and **it is highly recommended to change the default handler root path to a folder that is not publicly accessible**.

That can be done by:

- setting `WONOLOG_DEFAULT_HANDLER_ROOT_DIR` environment variable to the desired path
- using the `'wonolog.default-handler-folder'` filter to return the desired path. Callbacks attached to the filter will initially receive the value of `WONOLOG_DEFAULT_HANDLER_ROOT_DIR`  if set, or `{WP_CONTENT_DIR}/wonolog/` full path when the environment variable is not set.

There other configurations possible for Wonolog default handler, all available via filter hooks.



#### Default handler filter hooks

The filter **`wonolog.default-handler-filename`** allows to edit the file name format. By default its value is `'{date}.log'` where `{date}` is replaced by the value returned by PHP `date()` function, using the default date format or the custom one returned by callbacks attached to `'wonolog.default-handler-date-format'` filter hook. Note that `{date}` is required to be part of filename format.

The filter **`wonolog.default-handler-date-format`** allows to change the default date format, that is `'Y/m/d'`. The result of PHP `date()` function, when used with this format, will be replaced in the filename format string, bu default  `'{date}.log'`, but as liable to configuration thanks to `'wonolog.default-handler-filename'` filter hook.

The filter ** `wonolog.default-handler-bubble`**   allows to configure the "bubble" property of default handler, by default `true`. When an handler has bubble property set to false, the records it  handles, will not be propagated to other handlers. When default handler is the only handler, bubble property has no effect for obvious reasons, but it is quite important if more handlers are added to same logger.

The filter **`wonolog.default-handler-use-locking`**  filter tells the Monolog `StreamHandler` used by Wonolog default handler to acquire exclusive lock on the log file to be written. Default value is `true`.



### Replace default handler

As seen in previous section, default handler is very configurable, but many times is desirable to have a completely custom handler (nothing that writes to files, for example).

Or it is even possible one wants to don't configure any default handler at all, but different handler for different loggers.

All this is very possible with Wonolog.

Replace default handler is very easy: just create an instance of an object implementing `Monolog\Handler\HandlerInterface` (better if  extending `Monolog\Handler\AbstractProcessingHandler`) and pass it as first argument to `Inpsyde\Wonolog\bootstrap()` function.

For example, a mu plugin to configure Wonolog to use a New Relic handler as default handler might look like this:

```php
/*
 * Plugin name: Logging configuration
 */
use Inpsyde\Wonolog;
use Monolog\Handler;
use Monolog\Logger;

if ( ! defined( 'Inpsyde\Wonolog\LOG' )  ) {
  return;
}

Wonolog\bootstrap( new Handler\NewRelicHandler ( Logger::ERROR, true, 'my_new_relic_app' ) );
```



### Bootstrap flags

When `bootstrap()` function is called, it does the following:

1. instantiate the default handler (if no custom handler is passed) and set it up to be used for all loggers
2. instantiate the default processor and set it up to be used for all loggers
3. instantiate the PHP error handler to log core PHP errors and uncaught exceptions
4. instantiate and setup all the shipped hook listeners

Each of this four operations is related to a flag, that is a constant in the `Inpsyde\Wonolog` namespace.

They are, in order:

- `USE_DEFAULT_HANDLER`
- `USE_DEFAULT_PROCESSOR`
- `LOG_PHP_ERRORS`
- `USE_DEFAULT_HOOK_LISTENERS`


The `bootstrap()` function accepts a bitmask of these constants as second argument, to allow to enable only some of the features.

The default value for the constant is `USE_DEFAULT_ALL` that equals to use a bitmask of all four flags.

There's yet another flag that is `USE_DEFAULT_NONE` that is what needs to be used to disable all of them.

For, example, with the following code:

```php
Wonolog\bootstrap( null, Wonolog\USE_DEFAULT_NONE );
```

Wonolog will be bootstrapped, but  none of the four default tasks will be performed and so no log record will be kept, unless further configuration is used.

Don't fear, `Wonolog\bootstrap()` function returns an instance of `Wonolog\Controller`: an object that provides an API to configure Wonolog according to any needs.




## The `Controller` API



### Log PHP errors

The flag `LOG_PHP_ERRORS` passed to `bootstrap()` function tell's Wonolog to use the Wonolog error (and exception) handler described in the [Wonolog PHP error handler](#wonolog-php-error-handler) section.

The same result can be obtained by calling **`log_php_errors()` ** on the controller object returned by `bootstrap()`.

```php
Wonolog\bootstrap( null, Wonolog\USE_DEFAULT_NONE )
	->log_php_errors();
```

However, explicitly calling `log_php_errors()` allows to pass an argument to it, that lets configure the types of errors that must be logged by Wonolog.

For example:

```php
Wonolog\bootstrap( null, Wonolog\USE_DEFAULT_NONE )
	->log_php_errors( E_ALL ^ E_DEPRECATED );
```

As shown above, the argument accepted by  `log_php_errors()` is the same of the second argument accepted by PHP [`set_error_handler`](http://php.net/manual/en/function.set-error-handler.php) function.



### Customize default handler

When `null` is passed as first bootstrap argument, and the flag `USE_DEFAULT_HANDLER` is not part of the flags bitmask no default handler will be initialized, which means that no log will be done unless some handlers are added.

The `Controller` object returned by `bootstrap()` provides a method **`use_default_handler()`** that can be used with no arguments telling Wonolog to use its default handler (see [Wonolog default handler](#wonolog-default-handler) section) or can be used passing a custom handler to tell Wonolog to use it as default handler.

For example:

```php
Wonolog\bootstrap( null, Wonolog\USE_DEFAULT_NONE )
	->use_default_handler( new Handler\NewRelicHandler ( Logger::ERROR, true, 'my_new_relic_app' ) );
```

A note: if `USE_DEFAULT_HANDLER` flag is used in the `bootstrap()` function, or when an handler instance is passed as first argument to it, calling `use_default_handler()` on the returned `Controller` object will do nothing, because the default handler is already set by `bootstrap()`.



### Add more handlers

One of the things that makes Monolog so flexible for logging is possibility to have more handlers for each channel.

Based on its log level, each record will then be handled by loggers that declare a compatible minimum level until there's no more handlers or one of them stop to "bubble" the record to subsequent handlers.

However, as of now, we only seen how to add the "default" handler, that will be added to _all_ loggers, so now is time to see how to add additional handlers can be added.

The controller object returned by `bootstrap()` has a method, **`use_handler()`** for the scope.

Its first argument and only mandatory argument must be an instance of Monolog handler.

Using the method with only one argument, will add the handler to all the loggers.

As a second argument, it is possible to pass an array of channel names, to tell Wonolog to use the handler only for loggers assigned to specific channels.

For example, the following could be the entire mu-plugin code necessary to configure Wonolog:

```php
use Inpsyde\Wonolog;
use Monolog\Handler\NativeMailerHandler;
use Monolog\Logger;

if ( ! defined('Inpsyde\Wonolog\LOG') ) {
  return;
}

// Tell default handler to use given folder for logs
putenv( 'WONOLOG_DEFAULT_HANDLER_DIR=/etc/logs' );

$email_handler = new NativeMailerHandler(
  'alerts@example.com',              // to
  'Security alert from example.com', // subject
  'logs@example.com',                // from
  Logger::ERROR                      // minimum level
);

Wonolog\bootstrap()->use_handler( $email_handler, [ Wonolog\Channels::SECURITY ] );
```

The snippet above, tells Wonolog to use the default Wonolog handler for all loggers, and via environment variable tells default handler where to save log files.

Moreover, for the channel `Channels::SECURITY` (one of the five default Wonolog channels) an handler is setup to send log records with a minimum level of `Logger::ERROR` via email. The handler is provided by Monolog.

There's an additional optional arguments that `use_handler()` takes, it is `$handler_id`. This is an unique id to assign to the handler in Wonolog.

When provided, this id can be used to uniquely identify an handler in Wonolog.

For example, as explained below in [Add more processors](#add-more-processors) section, Wonolog allows to add processors to specific handlers, and to be able to identify handlers for the scope it is necessary to know the handler id.

Another reason to use handler id is is that during its execution Wonolog triggers some hooks passing handlers as hook argument for further configuration. In those hooks the given handler id will also be passed as hook argument, allowing to uniquely distinguish handlers form inside hook callbacks.



### Customize default processor

When using `USE_DEFAULT_PROCESSOR` as part of the flags bitmask passed to `bootstrap()` Wonolog will initialize its default processor (that adds WordPress context information to each log record).

The same result can be obtained by calling **`use_default_processor()`** on the controller returned by  `bootstrap()`.

Passing a custom processor (that is any callable) to `use_default_processor()` is possible to tell Wonolog to use it as default processor instead of shipped default processor.

For example:

```php
Wonolog\bootstrap( null, Wonolog\USE_DEFAULT_NONE )
  	->use_default_handler()
	->use_default_processor( [ new MyCustomRecordProcessor(), 'process' ] );
```

As shown in the snippet above, all the controller methods implements fluent interface, that is they return the same instance, allowing to call further methods.



### Add more processors

In Monolog, processors are callbacks that receive the record array and return a (maybe) altered value before the record is passed to handlers.

Processors are used in Monolog at two different steps: **there are processors that are assigned to _loggers_ and processors that are assigned to _handlers_**.

The Monolog processing work-flow is:

1. record array is created
2. based on the channel, it will be assigned to a logger
3. all processors assigned to the logger are executed and the obtained array is passed to the handlers assigned to the logger
4. each logger handler, will decide if log the record or not, based on its minimum level and record level. If the handler will handle the record, all processors assigned to the handler will process the record before actually handling.

This means that handler-specific processors performed by an handler have no effect on _other_ handler-specific processors, but processors assigned to loggers will have effect on all the records of a given channel.

The default Wonolog processor is assigned to loggers of all channels, it means that it will have effect on all the log records processed by Wonolog.

The controller object returned by `bootstrap()` provides API to add processors to loggers or specific handlers.



###### Adding processors to loggers

**`Controller::use_processor()`** method is what needs to be used to add processors to some or all loggers.

The first and only required argument is a callback (anything that is `callable` in PHP) that will be used as processor.

If not other arguments are provided, the processor will be used for all channels (which means will affect all the log records, just like Wonolog default processor).

Passing as second argument an array of channels names, it is possible to tell Wonolog to use the processor only for loggers of specific channels.

For example:

```php
Wonolog\bootstrap()
  ->use_processor( 'My\App\security_logs_processor', [ Wonolog\Channels::SECURITY ] );
```

The snippet above will add a custom function as processors for all the records belonging to security channel.



###### Adding processors to handlers

**`Controller::use_processor_for_handlers()`** method is what needs to be used to add processors to some or all handlers.

The first and only required argument is a callback (anything that is `callable` in PHP) that will be used as processor.

If not other arguments are provided, the processor will be used for all handlers (which means will affect all the log records, just like Wonolog default processor).

Adding processors to all handlers, means they will affect all log records processed by Wonolog. However, note that according to the Monolog work-flow described above, processors for handlers will be processed _later_ than processors for loggers.

Passing as second argument an array of handler ids, it is possible to tell Wonolog to use the processor only for loggers of specific handlers.

For example:

```php
Wonolog\bootstrap()
  ->use_handler( $some_handler, [], 'some_handler_id' )
  ->use_handler( $another_handler, [ Wonolog\Channels::SECURITY ], 'another_handler_id' )
  ->use_processor_for_handlers( 'My\App\custom_processor', [ 'some_handler_id', 'another_handler_id' ] );
```

The snippet above first adds two handlers, the first for all channels, the second only for security channel, finally it adds a processor to be used for those two handlers, referencing them by id.



###### Processors id

Both `use_processor()` and  `use_processor_for_handlers()` methods accept as third argument an unique id for the processor.

This will be passed as argument by some hooks triggered by Wonolog, and allows to distinguish processors inside hook callbacks. More on this in the [Configure handlers via hooks](#configure-handlers-via-hooks]) section.



### Customize default hook listeners

The flag `USE_DEFAULT_HOOK_LISTENERS` tell's Wonolog to use all the shipped hook listeners. The list of shipped listeners can be found in the [Shipped hook listeners](#shipped-hook-listeners) section.

The same result can be obtained by calling **`use_default_hook_listeners()`** on the returned controller object.

Wonolog also offers the possibility to choose which listener to use, by using  **`use_hook_listener()`** on the controller, passing as argument the listener to be used. 

For example:

```php
Wonolog\bootstrap( $my_default_handler, Wonolog\LOG_PHP_ERRORS|Wonolog\USE_DEFAULT_PROCESSOR )
  ->use_hook_listener( new HookListener\DbErrorListener() )
  ->use_hook_listener( new HookListener\FailedLoginListener() )
  ->use_hook_listener( new HookListener\HttpApiListener() )
  ->use_hook_listener( new HookListener\MailerListener() )
  ->use_hook_listener( new HookListener\QueryErrorsListener() )
  ->use_hook_listener( new HookListener\CronDebugListener() )
  ->use_hook_listener( new HookListener\WpDieHandlerListener() );
```

The above snippet tells Wonolog to use all default listeners, and actually equals to just call `use_default_hook_listeners()`, but of course it is possible only use just the desired listeners.

Note that the `use_hook_listener()` is also the method that needs to be used to tell Wonolog to use **custom listeners **.



### Custom hook listeners

The suggested way to log custom code with Wonolog is to use custom hook listeners. This allows the code to be logged to be free of any `do_action( 'wonolog.log' )` call, and use instead "proprietary" hooks.

To make this work, it is necessary that code to be logged, fires hooks when "meaningful things" happen during the execution, that will allow Wonolog to listen to those hooks and add long records.

Let's clarify with an example.

Let's assume there's a plugin named "MyFiles" that handle upload and download of files for logged-in WordPress users.

This plugin will fire some hooks:

```php
// somewhere in the plugin code...
do_action( 'myfiles_file_uploaded', $file_info, $uploader_user_id );
// ...
do_action( 'myfiles_file_upload_failed', $file_info, $failing_reson, $uploader_user_id );
// ...
do_action( 'myfiles_file_downloaded', $file_name, $downloader_user_id );
```

For the website where this plugin is installed, we can write a Wonolog hook listeners that will look more or less like this (the following code is PHP 5.6+):

```php
namespace MyWebiste;

use Inpsyde\Wonolog\HookListener\ActionListenerInterface;
use Inpsyde\Wonolog\Data;

class MyFilesListener implements ActionListenerInterface {
  
  const TARGET_CHANNEL_NAME = 'MyFilesPlugin';
  
  public function id() {
    return 'MyFiles Listener';
  }
  
  public function listen_to() {
    return [ 'myfiles_file_uploaded', 'myfiles_file_upload_failed', 'myfiles_file_downloaded', ];
  }  
  
  public function update( array $args ) {
    $method = [ $this, current_filter() ];
    if ( is_callable( $method ) ) {
      return $method( ...$args );
    }
  }  
  
  private function myfiles_file_uploaded( $file_info, $user_id ) {
    return new Data\Debug(
      'A file has been uploaded.',      // message
      self::TARGET_CHANNEL_NAME,        // channel
      compact( 'file_info', 'user_id' ) // context
    );
  }
  
  private function myfiles_file_upload_failed( $file_info, $reason, $user_id ) {
     return new Data\Error(
      "A file download failed because: {$reason}.",
      self::TARGET_CHANNEL_NAME,
      compact( 'file_info', 'user_id' )
    );
  }
  
  private function myfiles_file_downloaded( $file_name, $user_id ) {
    return new Data\Debug(
      'A file has been downloaded',
      self::TARGET_CHANNEL_NAME,
      compact( 'file_name', 'user_id' )
    );
  }
  
}
```

Things to note in code above:

`listen_to()` returns the list of all actions the listener targets

`update()` method is called when each of those listened hooks is fired, and all the arguments passed to the hook are passed as array to the method. Based on the actual hook, a different private method is then called, passing received hook arguments in order (thanks to PHP 5.6 variadic arguments)

Each argument returns an instance of Wonolog log object, that will be handled by Wonolog according to its configuration (handlers, processors, channels...)

All the log objects, uses a custom channel, 'MyFilesPlugin'. Being a custom channel, Wonolog will be able to handle it only if it knows about it. See [Wonolog channels](#wonolog-channels) section for the how to.



###### Using the custom listener

When the lister above is available, we still have to tell Wonolog to use it.

The mu-plugin code to do that could be something like this:

```php
use Inpsyde\Wonolog;
use Monolog\Handler\NativeMailerHandler;
use Monolog\Logger;
use MyWebiste\MyFilesListener;

if ( ! defined('Inpsyde\Wonolog\LOG') ) {
  return;
}

// add the custom channel to Wonolog
add_filter( 'wonolog.channels', function( array $channels ) {  
  $channels[] = MyFilesListener::TARGET_CHANNEL_NAME;
  
  return $channels;
} );

Wonolog\bootstrap()->use_hook_listener( new MyFilesListener() );
```



###### What about filters?

When possible it is preferable to use actions to trigger log records. Because filter needs to have a return value and very likely we don't want that doing a log changes the return value.

However, especially if the code we want to log is not under our control or we don't want to edit it for any reason, it could be necessary to use filters in hook listeners.

This is why Wonolog ships  with a `FilterListenerInterface`, it can be used instead of or alongside `ActionListenerInterface` if listened hooks are filters.

Considering that callbacks attached to filters must return something in WordPress, and considering that  `update()` method returns null or log data objects, `FilterListenerInterface`  has an additional method `filter()` that receives all the arguments passed to filter as array (just like `update()`) and have to return the filter return value.

If you don't want the hook listener to affect result, something like:

```php
   public function filter( array $args ) {
     return $args[0];
   }
```

will do.



###### Custom hook priority

Neither `ActionListenerInterface` or `FilterListenerInterface`  provide a way to specify the priority that Wonolog has to use to listen to hooks.

By default Wonolog uses a very late  priority, which is fine in most cases.

But we know that there are always edge cases.

For this reason, there's an additional interface: `HookPriorityInterface`.

This interface have `priority()` method that has to return the priority to use (the returned value will be used when Wonolog calls `add_action()` / `add_filter()` for the listened hooks).

The value returned by `priority()` will be used for **all** the listened hooks. To control the priority on a per-hook basis, without creating a different hook listener, Wonolog provides a filter: `'wonolog.listened-hook-priority'` that can be used for the scope.

This filter will pass to callbacks the current priority as first argument, and the hook as second, allowing to change priority on a per-hook basis. The first hook argument is initially set to default priority used by Wonolog or to the priority returned by `priority()` for listeners implementing `HookPriorityInterface`.

 It means that priority can be customized even without implementing `HookPriorityInterface` but only hooking `'wonolog.listened-hook-priority'`.

For example, the `MyFilesListener`  class above could do something like this:

```php
class MyFilesListener implements ActionListenerInterface {
 
   // ...
  
  public function listen_to() {
    
    $target_hooks = [
      'myfiles_file_uploaded'      => 0,
      'myfiles_file_upload_failed' => 20,
      'myfiles_file_downloaded'    => 999,
    ]
    
    add_filter( 'wonolog.listened-hook-priority', function( $priority, $hook  ) use( $target_hooks ) {
      return isset( $target_hooks[$hook] ) ? $target_hooks[$hook] : $priority;
    }, 10, 2 );
    
    return array_keys( $listened_hooks );
  }
  
   // ...
}
```



## Handlers and processors hooks

The controller API should provide a way to setup Wonolog in any desired way.

However, the controller API is accessible only in the mu-plugin that bootstraps Wonolog. 

If for any reason some further configuration for handlers and processors is necessary, Wonolog provides some hooks that allows loggers,  handlers and processors configuration.



### Configure loggers via hooks

In the sections above explained how `use_handler()`, `use_processor()` and `use_processor_for_handlers` controller method scan be used to add more handlers and processors to be used by Wonolog. 

The same operations can be done by using the hook **'wonolog.logger'**.

It is triggered just before the first time a logger is used, and  pass the logger as as argument. By exposing the Monolog object, it is possible to use [Monolog API](https://github.com/Seldaek/monolog/blob/master/src/Monolog/Logger.php) (`Logger::push_handler()`, `Logger::push_processor()`) to add handlers to the logger.

`Logger::getName()` method can be used to access the channel name inside hook callbacks, being allowed to add handlers and processors only for loggers of specific channels.

For example:

```php
add_action( 'wonolog.logger', function( Monolog\Logger $logger ) {
  
     $loggers_processor = 'My\App\loggers_processor';   // a processor for all loggets
     $handlers_processor = 'My\App\handlers_processor'; // a for handlers instantiated below
       
     $some_handler = // instantiate an handler here... 
     $some_handler->pushProcessor( $handlers_processor ); // add handler processor
     
     // add handler and processor to logger
     $logger
       ->pushHandler( $some_handler );
       ->pushProcessor( $loggers_processor );
     
     // only for the security logger...
     if ( $logger->getName() === Channels::SECURITY ) {
       
       $security_handler = // instantiate another handler here... 
       $security_handler->pushProcessor( $some_processor ); // add handler processor
       
       $logger->pushHandler( $security_handler );  // add security handler to the logger
     }
} );
```



The snippet above has the exact same effect of doing:

```php
$some_handler = // instantiate an handler here... 
$security_handler = // instantiate another handler here... 

Wonolog\bootstrap()
  ->use_handler( $some_handler, [], 'some_handler' )
  ->use_handler( $security_handler, [ Wonolog\Channels::SECURITY ], 'security_handler' )
  ->use_processor( 'My\App\loggers_processor' )
  ->use_processor_for_handlers( 'My\App\handlers_processor', ['some_handler', 'security_handler'] );
```



Latter snippet is surely more concise and probably preferable, but can only be done in the mu-plugin that bootstraps Wonolog.

Moreover, the controller API method necessitate the security handler to be instantiated on bootstrap, no matter if it will be used or not. This is not an issue if instantiation of the object is trivial (as it probably should be), but if that's not the case, the **'wonolog.logger'** hook can be used to perform just in time logger configuration.



### Configure handlers via hooks

When using `use_handler()`, when Wonolog is bootstrapped there's the possibility to configure the handler as necessary. 

But there might be cases in which we need to configure an handler that was added using `use_handler()` but outside the plugin that bootstrap Wonolog.

That's possible thanks to the **'wonolog.handler-setup'**  hook.

This hook is triggered by Wonolog just before the first time an handler is used.

The handler is passed as hook first argument, and can be configured as needed. 

The hook passes as second argument the handler id that was used with `use_handler()` . If no id was used, as it is optional, the second argument will contain an id calculated via `spl_object_hash() ` that guarantees uniqueness but unfortunately is not predicable.

Finally the hook passes as third argument an instance of a class named `ProcessorsRegistry`. This object allows to "find" processors that where added via controller methods `use_processor()` and `use_processor_for_handlers()`.

For example, if Wonolog bootstrapping was something like:

```php
Wonolog\bootstrap()
  ->use_handler( $some_handler, [], 'some_handler' )
  ->use_handler( $another_handler, [], 'another_handler' )
  ->use_processor_for_handlers(
     [ MyApp\CustomProcessor(), 'process' ],
     [ 'some_handler' ],
     'my_custom_processor'
   );
```

There are two handlers added, and a processor added to only one of them identified by its id (`'some_handler'`).

Now let's assume that we want to add the same processor also for the handler with id `'another_handler'` but for some reason we can't or don't want to edit the MU plugin where code above is located.

In some other place, we could leverage  **'wonolog.handler-setup'**  hook for the scope, like this:

```php
add_action(
  'wonolog.handler-setup',
  function( HandlerInterface $handler, $handler_id, ProcessorsRegistry $processors ) {
    if ( $handler_id === 'another_handler' ) {
      $handler->pushProcessor( $processor->find( 'my_custom_processor' ) );
    }
  },
  10,
  3
);
```

The code above push the same processors that was added via `use_processor_for_handlers()` finding it by its id, that is what was passed as third argument to `use_processor_for_handlers()`.

This relies on the fact that the the processor id argument was used (it is optional). Worth noting, however that is some cases it could be possible to guess the processor id even when it was not passed to `use_processor_for_handlers()` .

In fact, processors are PHP `callable`, which can be strings (function names), arrays (static or dynamic object methods) or objects (closures, invokable objects).
When processors are functions, their processor id, if not provided, is assumed by Wonolog to be the function name itself. 
When processors are static methods, their processor id, if not provided, is assumed by Wonolog to be the a string  in the form `'Fully\Qualified\ProcessorClassName::methodName'`.
When processors are dynamic methods, if processor id was not provided, it is calculated by Wonolog using `spl_object_hash()` that is not predictable and so in those cases the only chance to find the registered processor inside **'wonolog.handler-setup'** is that a custom predicate id was passed as third argument to  `use_processor` or `use_processor_for_handlers`.



# License

Copyright (c) 2016 Inpsyde GmbH.

Wonolog code is licensed under [MIT license](https://opensource.org/licenses/MIT).

The team at [Inpsyde](https://inpsyde.com) is engineering the Web since 2006.
