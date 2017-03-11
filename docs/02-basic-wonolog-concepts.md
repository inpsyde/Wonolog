# Basic Wonolog Concepts

## Table of contents

- [Wonolog Main Logging Hook](#wonolog-main-logging-hook)
- [Log Record Data](#log-record-data)
- [Log Record Data as Wonolog Objects](#log-record-data-as-wonolog-objects)
- [Log Record Data from `WP_Error` Objects](#log-record-data-from-wp_error-objects)
- [Log Record Data from Exceptions](#log-record-data-from-exceptions)
- [Level-rich Log Hooks](#level-rich-log-hooks)


## Wonolog Main Logging Hook

One of the goals of Wonolog is to allow for plugins and themes to be compatible with it, without requiring it as a dependency.

For this reason, logging in Wonolog is done via a WordPress function: `do_action()`.

The main hook to use for the scope is `'wonolog.log'`.

A bare-minimum example of logging with Wonolog could look like so:

```php
do_action( 'wonolog.log', 'Something happened.' );
```

This is nice and easy, however, there are a few thing missing, the most important ones being the *channel* and the *level* of the log event.

It still works because Wonolog will _kindly_ set them according to defaults, but in general it is better to take control over this.


## Log Record Data

In Monolog (and Wonolog), every log record comes with the following information:

| Data Key     | Type    | Description                              |
| ------------ | ------- | ---------------------------------------- |
| `'message'`  | string  | Textual description of the event.     |
| `'channel'`° | string  | The name of the logger to use. Every logger will handle the log differently. |
| `'level'`°°  | integer | "Severity" of the event.                 |
| `'context'`  | array   | Arbitrary data providing context to the log record. |

° *There are no default channels in Monolog, but there are some default channels in Wonolog. These are accessible via class constants on the `Channels` class. More on this below.*

°° *The log level is normally represented by an integer, that can be compared in order of severity with the levels defined by PSR-3, which assign to each of the eight log levels a name (as interface constant) and an integer (as constant value).*

An example of logging an array of data with Wonolog could be this:

```php
do_action( 'wonolog.log', [
    'message' => 'Something happened.',
    'channel' => 'DEBUG',
    'level'   => 100,
    'context' => [],
] );
```

By using arrays containing log data, and `do_action()` to trigger the actual logging, Wonolog allows plugins and themes to be compatible without requiring Wonolog as a dependency.
If code like the above is executed without Wonolog being available, nothing (bad) happens.

Moreover, at any point it would be possible to hook the log event with some other logging package, being able to log data in a different way without changing any code.


## Log Record Data as Wonolog Objects

Even if arrays are a good way to make code to be logged not dependent on Wonolog, there are cases when it is desirable to write code that is dependent on Wonolog _on purpose_ (e.g., when you are developing a Wonolog extension).

In those cases, it's important to know that, internally, Wonolog handles log data in the form of objects implementing `Inpsyde\Wonolog\Data\LogDatainterface`.

There are a few of them.
The simplest one is `Inpsyde\Wonolog\Data\Log`, whose constructor signature is the following:

```php
public function __construct(
    $message = '',
    $level = Logger::DEBUG,
    $channel = Channels::DEBUG,
    array $context = []
)
```

By using this object, a log event that does the same as the array example from above looks like so:

```php
do_action( 'wonolog.log', new Data\Log( 'Something happened.', Logger::CRITICAL ) );
```

Wonolog also ships with a series of objects that represent specific error levels, making the triggering of events less verbose.
All the objects are in the `Inpsyde\Wonolog\Data` namespace, and there's an object for each log level.

The constructor of those objects is the same as `Inpsyde\Wonolog\Data\Log`, except that they don't take any `$level`, as it is specific per object.

For example, the same result of the example above could be obtained with this:

```php
do_action( 'wonolog.log', new Data\Critical( 'Something happened.' ) );
```


## Log Record Data from `WP_Error` Objects

When dealing with WordPress code, it's easy to encounter functions that return `WP_Error` instances when something goes wrong.

To integrate with such code easier, Wonolog supports log record data to be passed as `WP_Error` objects.

The log record will include both the message and context of the `WP_Error` object, taken via `WP_Error::get_error_message()` and `WP_Error::get_error_data()`, respectively.

Both the channel and level can be passed explicitly, or Wonolog will try to guess them from the error code.

While this _guessing_ has higher chances to work with `WP_Error` instances returned by WordPress core, it could not work that well with custom error objects.

For example, assuming a `WP_Error` like this:

```php
global $wpdb;

$wp_error = new \WP_Error( $wpdb->last_error, 'wpdb_error', [ 'query' => $wpdb->last_query ] );
```

With this, it is possible to do the following:

```php
do_action( 'wonolog.log', $wp_error );
```

Wonolog will recognize the message, the context, the channel (`Channels::DB`), and it will set the level to `Logger::ERROR`.

The level can also be set explicitly including a second argument in the `do_action` call, like so:

```php
do_action( 'wonolog.log', $wp_error, Logger::CRITICAL );
```

Also, the channel can be set explicitly, providing a third argument:

```php
do_action( 'wonolog.log', $wp_error, Logger::WARNING, Channels::DEBUG );
```


## Log Record Data from Exceptions

Another common use case is to log an exception thrown during execution of code.

While it might be worth noting that **uncaught exceptions** will be logged by Wonolog automatically (by default), your code, hopefully, catches any thrown exception, and hence you might want to log them manually.

An example usage could be like the following:

```php
try {
    // Do somethig here.
} catch( \Exception $exception ) {
    // Log the exception, by directly passing it to Wonolog.
    do_action( 'wonolog.log', $exception );

    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        // When in debugging mode, we want to see this popping up.
        throw $exception;
    }

    // Debugging is turned off, silence is golden in production.
}
```

Note that Wonolog also works well with PHP 7+ `\Throwable`.

When the only argument passed to the `wonolog.log` action is the exception instance, the level of the log event is assumed to be `LogLevel::ERROR`, and the channel `Channels::DEBUG`.
However, it is possible to explicitly pass error level and error channel, like this:

```php
do_action( 'wonolog.log', $exception, Logger::CRITICAL, Channels::DB );
```


## Level-rich Log Hooks

So far, we only used a single Wonolog action: **`'wonolog.log'`**.

However, there are more action hooks that can be used to log data.

These are hooks that **embed error level**.

For example, the following log code:

```php
do_action( 'wonolog.log', [ 'message' => 'Please log me!', 'level' => 'INFO' ] );
```

can also be written like so:

```php
do_action( 'wonolog.log.info', [ 'message' => 'Please log me!' ] );
```

This could make logging action calls more concise, even without relying on Wonolog objects (and thus not making code using these hooks dependent on Wonolog).

There is one hook for each of the PSR-3 log levels, so we have:

- `'wonolog.log.emergency'`
- `'wonolog.log.alert'`
- `'wonolog.log.critical'`
- `'wonolog.log.error'`
- `'wonolog.log.warning'`
- `'wonolog.log.notice'`
- `'wonolog.log.info'`
- `'wonolog.log.debug'`

In case one of the above hook is used passing some data that *also* contains hook level information, the **level with higher severity _wins_**.

Let's have some examples:

```php
// In this case, the logged level will be "ERROR", because it has a higher severity than "DEBUG".
do_action( 'wonolog.log.debug', [ 'message' => 'Please log me!', 'level' => 'ERROR' ] );

// In this case, the logged level will be "CRITICAL", because it has higher severity than "ERROR".
do_action( 'wonolog.log.critical', [ 'message' => 'Please log me!', 'level' => 'ERROR' ] );
```

The same applies if the data is provided as Wonolog log data object:

```php
// In this case, the logged level will be "ERROR", because it has higher severity than "DEBUG".
do_action( 'wonolog.log.error', new Data\Debug( 'Please log me!' ) );

// In this case, the logged level will be "CRITICAL", because it has higher severity than "ERROR".
do_action( 'wonolog.log.critical', new Data\Error( 'Please log me!' ) );
```


----

Read next:

- [03 - A Deeper Look at Wonolog](03-a-deeper-look-at-wonolog.md) to learn more advanced concepts and features of Wonolog.
- [04 - Hook Listeners](04-hook-listeners.md) to read about hook listeners, the powerful feature of Wonolog that allows for logging any WordPress code.
- [05 - Wonolog Customization](05-wonolog-customization.md) for a deep travel through all the possible configurations available for any aspect of the package.
- [06 - Custom Hook Listeners](06-custom-hook-listeners.md) to see a complete example of a custom hook listener, its integration in Wonolog, and all the things that you need to know in order to write reusable Wonolog extensions.

Read previous: 

- [01 - Monolog Primer](01-monolog-primer.md) to learn a bit more about Monolog core concepts.

-------

[< Back to Index](https://github.com/inpsyde/wonolog/)
