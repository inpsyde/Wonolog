# Basic Wonolog concepts

## Table of contents

- [Wonolog main logging hook](#wonolog-main-logging-hook)
- [Log record data](#log-record-data)
- [Log record data as Wonolog objects](#log-record-data-as-wonolog-objects)
- [Log record data from `WP_Error` object](#log-record-data-from-wp_error-object)
- [Log record data from exceptions](#log-record-data-from-exceptions)
- [Level-rich log hooks](#level-rich-log-hooks)


## Wonolog main logging hook

One of the aim of Wonolog is to allow plugins and themes to be compatible with it, without requiring it as a dependency.

For this reason, logging in Wonolog is done via a WordPress function: `do_action()`.

The main hook to use for the scope is **`wonolog.log`**.

A bare-minimum example of logging with Wonolog could be:

```php
do_action( 'wonolog.log', 'Something happen.' );
```

This is nice and easy, however there are a few thing missing, the most important are:

- the "channel" of the log event
- the "level" of the log event

It still works because Wonolog will _kindly_ set them with defaults, but in real world is better to have control on this.



## Log record data

In Monolog (and Wonolog) every log record comes with some information:

| Data key     | Type    | Description                              |
| ------------ | ------- | ---------------------------------------- |
| `"message"`  | string  | Textual description of the log event     |
| `"channel"`° | string  | The name of the logger to use. Every logger will handle the log differently |
| `"level"`°°  | integer | "Severity" of the event.                 |
| `"context"`  | array   | Arbitrary data that gives context to the log record. |

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

By using arrays as format to contain log data and `do_action` to trigger the log record, Wonolog allows plugins and 
themes to be compatible with Wonolog without requiring it as a dependency: if such code is ran when Wonolog is not available, nothing bad happen.

Moreover, at any point it would be possible to hook the log event with some other logging package being able to log data in a different ways without changing any code.



## Log record data as Wonolog objects

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


## Log record data from `WP_Error` object

When dealing with WordPress code it's easy to encounter functions / methods that return `WP_Error` instances 
when something goes wrong.

To make integration with such code easier, Wonolog supports log record data to be passed via `WP_Error` objects.

The log message will be taken from `WP_Error` (via `WP_Error::get_error_message()`) as well as log context 
(via `WP_Error::get_error_data()`).

Regarding channel and level, they could be passed explicitly or Wonolog will try to guess them from the error code.

This "guessing" has higher chances to work with `WP_Error` instances returned by core, could not work well with custom error objects.

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


## Log record data from exceptions

Another common use case is to log something when an exception is thrown during execution of code.

Worth nothing that uncaught exceptions will be logged by Wonolog automatically (by default) but, hopefully, your code catches any thrown exception and might be desirable to log them.

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

When the only argument passed to `wonolog.log` hook is the exception instance, the level of the log event is assumed to be `LogLevel::ERROR`, and the channel `Channels::DEBUG`, but it is possible to explicitly pass error level and error channel:

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

There is one hook per each PSR-3 log level, so we have:

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

----

Read next:

- [03 - A Deeper look at Wonolog](#https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/03-a-deeper-look-at-wonolog.md)
- [04 - Hook listeners](#https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/04-hook-listeners.md)
- [05 - Wonolog customization](#https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/05-wonolog-customization.md)
- [06 - Custom hook listeners](#https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/06-custom-hook-listeners.md)

Read previous: 

- [01 - Monolog Primer](#https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/01-monolog-primer.md)

-------

[< Back to index](#https://github.com/inpsyde/wonolog/)