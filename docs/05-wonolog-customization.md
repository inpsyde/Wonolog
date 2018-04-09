# Wonolog Customization

## Table of Contents

- [Safe Wonolog Bootstrapping](#safe-wonolog-bootstrapping)
- [Programmatically Disabling Wonolog](#programmatically-disabling-wonolog)
- [Bootstrap Configuration](#bootstrap-configuration)
    - [Wonolog Default Handler](#wonolog-default-handler)
        - [Default Handler Filter Hooks](#default-handler-filter-hooks)
        - [Replacing the Default Handler](#replacing-the-default-handler)
    - [Bootstrap Flags](#bootstrap-flags)
- [The `Controller` API](#the-controller-api)
    - [Logging PHP Errors](#logging-php-errors)
    - [Customizing the Default Handler](#customizing-the-default-handler)
    - [Adding Handlers](#adding-handlers)
        - [Handler IDs](#handler-ids)
    - [Customizing the Default Processor](#customizing-the-default-processor)
    - [Adding Processors](#add-processors)
        - [Adding Processors to Loggers](#adding-processors-to-loggers)
        - [Adding Processors to Handlers](#adding-processors-to-handlers)
        - [Processor IDs](#processor-ids)
    - [Customizing Default Hook Listeners](#customizing-default-hook-listeners)
- [Loggers and Handlers Hooks](#loggers-and-handlers-hooks)
    - [Configuring Loggers via Hooks](#configuring-loggers-via-hooks)
    - [Configuring Handlers via Hooks](#configuring-handlers-via-hooks)


## Safe Wonolog Bootstrapping

To be initialized, Wonolog needs the function `Inpsyde\Wonolog\bootstrap()` to be called in an MU plugin.

In many cases, it is safe enough to just do it, because following the suggested practice to install Wonolog via Composer at website level, when the MU plugin gets run, Wonolog will always be there.

However, one might want to check for function existence before calling it:

```php
if ( function_exists( 'Inpsyde\Wonolog\bootstrap' ) ) {
    Inpsyde\Wonolog\bootstrap();
}
```

While this works, the suggested practice is not to check for function existence, but to check the existence of the `Inpsyde\Wonolog::LOG` constant instead:

```php
if ( defined( 'Inpsyde\Wonolog\LOG' ) ) {
    Inpsyde\Wonolog\bootstrap();
}
```

That constant is defined in the same file as the bootstrap function, and there's not way the constant exists but the function doesn't.

Considering that checking a constant is faster than checking a function (constant is even defined at compile time, allowing for opcode cache optimization) and considering that such code will run on every single request to your website, we suggest this practice as it is a good compromise between safety and performance.


## Programmatically Disabling Wonolog

When (reasonably) the same code is deployed to all environments, it is not possible to enable or disable Wonolog per environment (and not being a plugin, there is no way to _deactivate_ it).

However, it is a quite common practice to disable logging on some environments.
This is why Wonolog supports an environment variable that can be used to turn it off completely: `WONOLOG_DISABLE`.

For example, by doing:

```php
putenv( 'WONOLOG_DISABLE=TRUE' );
```

Wonolog will do nothing, no matter if `Inpsyde\Wonolog\bootstrap()` is called and how.

Sometimes it may be necessary to enable or disable logging based on other conditions.
In those cases, Wonolog offers a filter, `'wonolog.disable'`, that can be used for the scope:

```php
// Let a callback decide if we want to have loggin enabled or not:
add_filter( 'wonolog.disable', 'My\App\should_logging_be_disabled' );

// Or, disable it right away:
add_filter( 'wonolog.disable', '__return_true' );
```

The filter will receive a single argument that is initially set according to the value of the `WONOLOG_DISABLE` environment variable.

Note: When you want to disable Wonolog no matter what, however, you would probably better not be calling `Inpsyde\Wonolog\bootstrap()` at all instead.


## Bootstrap Configuration


### Wonolog Default Handler

When bootstrapping Wonolog with all the defaults, the most notable thing that happens is probably the fact that a default handler is attached to the loggers of all channels.

When no handler is added, Monolog uses a handler for all loggers that just dumps the content of log record to "standard output".

This choice makes sense for a library like Monolog that leaves it to implementers to decide how logs should be handled by default.
But for Wonolog, being a project-specific package (it targets WordPress and nothing else), we thought that providing a *persistent* default handler out-of-the-box makes sense.

As mentioned in [Getting Started](../#getting-started), Wonolog uses a default handler built around Monolog's [`StreamHandler`](https://github.com/Seldaek/monolog/blob/master/src/Monolog/Handler/StreamHandler.php) that writes all the logs to a file whose path changes based on the record date.

The default root path for the log files is `{WP_CONTENT_DIR}/wonolog/`, and inside that, files are saved to a path using the format: `{Y/m/d}.log`.

**Please note: using the content folder to store logs is NOT a good idea**.

In fact, logs very likely contain sensitive data that, in the content folder, are publicly accessible, and **that is in the best case a privacy leak, and in the worst case a security threat**.

However, WordPress has no place for custom code to save files that must not be public.
So using a subfolder of the content directory is the only option we had to use **as default**.

Don't worry, everything regarding the default handler is very configurable, in more than one way, and **it is highly recommended to change the default handler root path to a folder that is not publicly accessible**.

That can be done by the following:

- setting the `WONOLOG_DEFAULT_HANDLER_ROOT_DIR` environment variable to the desired path;
- using the `'wonolog.default-handler-folder'` filter to return the desired path.

Callbacks attached to the filter will initially receive the value of the `WONOLOG_DEFAULT_HANDLER_ROOT_DIR` environment variable, if set, or the `{WP_CONTENT_DIR}/wonolog/` full path when the environment variable is not defined.

There are different other configurations possible for Wonolog default handler, all available via filter hooks.


#### Default Handler Filter Hooks

The filter **`wonolog.default-handler-filename`** allows to edit the file name format.
By default its value is `'{date}.log'`, where `{date}` is replaced by the value returned by PHP `date()` function, using the default date format or the custom one returned by callbacks attached to `'wonolog.default-handler-date-format'` filter hook.
Note that the string `{date}` is **required to be part of filename** format.

The filter **`wonolog.default-handler-date-format`** allows to change the default date format, that is `'Y/m/d'`.
The result of the PHP `date()` function used with this format will be replaced in the filename format string, by default `'{date}.log'`, but liable to configuration thanks to the `'wonolog.default-handler-filename'` filter hook.

The filter **`wonolog.default-handler-bubble`** allows to configure the "bubble" property of default handler, which is by default `true`.
When a handler has the bubble property set to false, the records it handles will not be propagated to other handlers.

The filter **`wonolog.default-handler-use-locking`** tells the Monolog `StreamHandler` used by the Wonolog default handler to acquire exclusive lock on the log file to be written.
The default value is `true`.


#### Replacing the Default Handler

As seen in the previous section, the default handler is very configurable, but many times is desirable to have a completely custom handler (nothing that writes to files, for example).

Or it is even possible one wants to not configure any default handler at all, but different handlers for different loggers.

All this is possible with Wonolog.

Replacing the default handler is very easy: just create an instance of an object implementing `Monolog\Handler\HandlerInterface` (or better: extending `Monolog\Handler\AbstractProcessingHandler`) and pass it as first argument to `Inpsyde\Wonolog\bootstrap()`.

For example, an MU plugin that configures Wonolog to use a New Relic handler as default handler might look like this:

```php
use Inpsyde\Wonolog;
use Monolog\Handler;
use Monolog\Logger;

if ( ! defined( 'Inpsyde\Wonolog\LOG' ) ) {
    return;
}

Wonolog\bootstrap( new Handler\NewRelicHandler ( Logger::ERROR, true, 'my_new_relic_app' ) );
```


### Bootstrap Flags

When the `bootstrap()` function is called, it does the following:

1. instantiate the PHP error handler to log core PHP errors and uncaught exceptions;
1. instantiate and set up all the shipped hook listeners;
1. instantiate the default handler (if no custom handler is passed) and set it up to be used for all loggers;
1. instantiate the default processor and set it up to be used for all loggers.

Each of these four operations is connected to a flag, that is a constant in the `Inpsyde\Wonolog` namespace:

- `LOG_PHP_ERRORS`
- `USE_DEFAULT_HANDLER`
- `USE_DEFAULT_HOOK_LISTENERS`
- `USE_DEFAULT_PROCESSOR`

The `bootstrap()` function accepts a bitmask of these constants as second argument, to allow enabling only some of the features.

The default value for this bitmask argument is another flag, `USE_DEFAULT_ALL`, that equals to the bitmask of all four flags.

There's yet another flag that is `USE_DEFAULT_NONE`, which is what needs to be used to disable all of them.

For example, with the following code:

```php
Wonolog\bootstrap( null, Wonolog\USE_DEFAULT_NONE );
```

Wonolog will be bootstrapped, but none of the four default tasks will be performed and so no log record will be kept, unless further configuration is done.

Don't fear, the `Wonolog\bootstrap()` function returns an instance of `Wonolog\Controller`: an object that provides an API to configure Wonolog according to any needs.


## The `Controller` API


### Logging PHP Errors

The flag `LOG_PHP_ERRORS` passed to the `bootstrap()` function tells Wonolog to use Wonolog's custom error (and exception) handler.

The same result can be obtained by calling **`log_php_errors()`** on the controller object returned by `bootstrap()`.

```php
Wonolog\bootstrap( null, Wonolog\USE_DEFAULT_NONE )
    ->log_php_errors();
```

Explicitly calling `log_php_errors()`, instead of using the flag, allows to pass an argument, that lets you configure the types of errors that should be logged by Wonolog.

An example:

```php
Wonolog\bootstrap( null, Wonolog\USE_DEFAULT_NONE )
    ->log_php_errors( E_ALL ^ E_DEPRECATED );
```

The argument accepted by `log_php_errors()` is the same as the second argument accepted by the PHP [`set_error_handler()`](http://php.net/manual/en/function.set-error-handler.php) function.


### Customizing the Default Handler

When `null` is passed as first bootstrap argument, and the flag `USE_DEFAULT_HANDLER` is not part of the flags bitmask, no default handler will be initialized, which means that no log will be written, unless some handlers are added.

The `Controller` object returned by `bootstrap()` provides a method **`use_default_handler()`**.
When called with no arguments, this is telling Wonolog to use its default handler.
When a custom handler gets passed as first argument, Wonolog will use it as default handler.

An example:

```php
Wonolog\bootstrap( null, Wonolog\USE_DEFAULT_NONE )
    ->use_default_handler( new Handler\NewRelicHandler( Logger::ERROR, true, 'my_new_relic_app' ) );
```

Note: if the `USE_DEFAULT_HANDLER` flag is used in the `bootstrap()` function, or when a handler instance is passed as first argument to it, calling `use_default_handler()` on the returned `Controller` object will do nothing, because the default handler is already set by `bootstrap()`.


### Adding Handlers

One of the things that makes Monolog so flexible is the possibility to have multiple handlers for each channel.

Based on its log level, each record will then be handled by loggers that declare a compatible minimum level, until there are no more handlers, or one of them stops to "bubble" the record to subsequent handlers.

So far, we only saw how to add the "default" handler that will be added to **all** loggers, so it is now time to see how additional handlers can be added.

The controller object returned by `bootstrap()` has a method **`use_handler()`** for this.

Its first and only mandatory argument must be an instance of a Monolog handler.

Using the method with only one argument will add the given handler to all the loggers.

As second argument, it is possible to pass an array of channel names to tell Wonolog to use the handler only for loggers assigned to these specific channels.

For example, the following could be the entire MU plugin code necessary to configure Wonolog:

```php
use Inpsyde\Wonolog;
use Monolog\Handler\NativeMailerHandler;
use Monolog\Logger;

if ( ! defined( 'Inpsyde\Wonolog\LOG' ) ) {
    return;
}

// Tell the default handler to use the given directory for logs.
putenv( 'WONOLOG_DEFAULT_HANDLER_ROOT_DIR=/etc/logs' );

$email_handler = new NativeMailerHandler(
    'alerts@example.com',              // to
    'Security alert from example.com', // subject
    'logs@example.com',                // from
    Logger::ERROR                      // minimum level
);

Wonolog\bootstrap()
    ->use_handler( $email_handler, [ Wonolog\Channels::SECURITY ] );
```

The above code tells Wonolog to use the default Wonolog handler for all loggers, and, via environment variable, where to save log files.

Moreover, for the channel `Channels::SECURITY` (one of the five default Wonolog channels) a handler is set up to send log records with a minimum level of `Logger::ERROR` via email.
The handler is provided by Monolog.


##### Handler IDs

There's an additional optional argument that `use_handler()` takes: `$handler_id`.

When provided, this ID can be used to uniquely identify a handler in Wonolog.

For example, as explained in [Adding Processors](#adding-processors), Wonolog allows to add processors to specific handlers.
And to be able to identify handlers for the scope, it is necessary to know the handler ID.

Another reason to use handler IDs is that during execution Wonolog triggers some hooks passing handlers as arguments for further configuration.
For those hooks, the given handler ID will also be passed as hook argument, allowing to uniquely distinguish handlers inside hook callbacks.


### Customizing the Default Processor

When using `USE_DEFAULT_PROCESSOR` as part of the flags bitmask passed to `bootstrap()`, Wonolog will initialize its default processor (that adds WordPress context information to each log record).

The same result can be obtained by calling **`use_default_processor()`** on the controller returned by `bootstrap()`.

BY passing a custom processor (i.e., any callable) to `use_default_processor()`, it is possible to tell Wonolog to use it as default processor instead of shipped default processor.

An example:

```php
Wonolog\bootstrap( null, Wonolog\USE_DEFAULT_NONE )
    ->use_default_handler()
    ->use_default_processor( [ new MyCustomRecordProcessor(), 'process' ] );
```

Note: obviously, the controller implements a fluent interface, that is each method returns the same instance on which it was called, allowing to chain further method calls.


### Adding Processors

In Monolog, processors are callbacks that receive the record array and return a possibly altered value before the record is passed to handlers.

Processors are used in Monolog at two different steps: **there are processors that are assigned to _loggers_, and there processors that are assigned to _handlers_**.

Handler-specific processors executed by a handler have no effect on _other_ handler-specific processors.
Processors assigned to loggers, on the other hand, will have effect on all the records of a given channel.

The default Wonolog processor is assigned to the loggers of all channels.
This means that it will have effect on all the log records processed by Wonolog.

The controller object returned by `bootstrap()` provides an API to add processors to loggers or to handlers.


#### Adding Processors to Loggers

The **`Controller::use_processor()`** method is what needs to be used to add processors to some or all loggers.

The first and only required argument is a callback that will be used as processor.

If no other arguments are provided, the processor will be used for all channels (which means it will affect all the log records, just like Wonolog's default processor).

BY passing as second argument an array of channels names, it is possible to tell Wonolog to use the processor only for loggers of these specific channels.

An example:

```php
Wonolog\bootstrap()
    ->use_processor( 'My\App\security_logs_processor', [ Wonolog\Channels::SECURITY ] );
```

The above code will add a custom function as processors for all the records in the security channel.


#### Adding Processors to Handlers

The **`Controller::use_processor_for_handlers()`** method is what needs to be used to add processors to some or all handlers.

The first and only required argument is a callback that will be used as processor.

Adding processors to all handlers means they will affect all log records processed by Wonolog.
However, note that according to the Monolog work-flow described above, processors for handlers will be processed _after_ processors for loggers.

By passing as second argument an array of handler IDs, it is possible to tell Wonolog to use the processor only for loggers of these specific handlers.

An example:

```php
Wonolog\bootstrap()
    ->use_handler( $some_handler, [], 'some_handler' )
    ->use_handler( $another_handler, [ Wonolog\Channels::SECURITY ], 'another_handler' )
    ->use_processor_for_handlers( 'My\App\custom_processor', [ 'some_handler', 'another_handler' ] );
```

The above code first adds two handlers, the first for all channels, the second only for security channel.
Finally it adds a processor to be used for those two handlers, referencing them by their individual ID.


#### Processor IDs

The `use_processor()` and `use_processor_for_handlers()` methods accept as third argument a unique ID for the processor.

This will be passed as argument by some hooks triggered by Wonolog, and allows to distinguish processors inside hook callbacks. 

More on this in [Configuring Handlers via Hooks](#configuring-handlers-via-hooks]).


### Customizing Default Hook Listeners

The flag `USE_DEFAULT_HOOK_LISTENERS` tells Wonolog to use all the hook listeners shipped with Wonolog.

The same result can be obtained by calling **`use_default_hook_listeners()`** on the returned controller object.

Wonolog also offers the possibility to choose which listener to use, by using  **`use_hook_listener()`** on the controller, passing as argument an instance of the listener to be used. 

An example:

```php
Wonolog\bootstrap( $my_default_handler, Wonolog\LOG_PHP_ERRORS|Wonolog\USE_DEFAULT_PROCESSOR )
    ->use_hook_listener( new HookListener\CronDebugListener() )
    ->use_hook_listener( new HookListener\DbErrorListener() )
    ->use_hook_listener( new HookListener\FailedLoginListener() )
    ->use_hook_listener( new HookListener\HttpApiListener() )
    ->use_hook_listener( new HookListener\MailerListener() )
    ->use_hook_listener( new HookListener\QueryErrorsListener() )
    ->use_hook_listener( new HookListener\WpDieHandlerListener() );
```

The above code tells Wonolog to use all default listeners, and actually equals to calling `use_default_hook_listeners()`.

Note that the `use_hook_listener()` method also needs to be used to tell Wonolog to use **custom listeners**.

Refer to [Custom Hook Listeners](06-custom-hook-listeners.md) for a complete example of a custom hook listener implementation and its integration in Wonolog.


## Loggers and Handlers Hooks

The API that the controller provides should be enough to set up Wonolog for all the possible use cases.

However, the controller API is accessible only in the MU plugin that bootstraps Wonolog.

If, for any reason, some further configuration for handlers and processors is necessary, Wonolog provides hooks that allow to configure loggers, handlers and processors from other places, very likely other MU plugins.


### Configuring Loggers via Hooks

In previous sections, we learned how the `use_handler()`, `use_processor()` and `use_processor_for_handlers()` controller methods can be used to add more handlers and processors to be used by Wonolog.

The same operations can be done by using the **'wonolog.logger'** action.

It is triggered just before the first time a logger is used, and it passes the logger as as argument.
By exposing the Monolog object, it makes it possible to use the [Monolog API](https://github.com/Seldaek/monolog/blob/master/src/Monolog/Logger.php) (e.g., `Logger::push_handler()`, and `Logger::push_processor()`) to add handlers and processors to the logger.

The `Logger::getName()` method can be used to access the channel name inside hook callbacks, allowing to add handlers and processors only for loggers of specific channels.

An example:

```php
add_action( 'wonolog.logger', function ( Monolog\Logger $logger ) {

    $loggers_processor = 'My\App\loggers_processor';   // A processor for all loggers.
    $handlers_processor = 'My\App\handlers_processor'; // A processor for handlers instantiated below.

    $some_handler = new SomeHandler( /* some args */ );
    $some_handler->pushProcessor( $handlers_processor ); // Add handlers processor.

    // Add handler and processor to logger.
    $logger
        ->pushHandler( $some_handler );
        ->pushProcessor( $loggers_processor );

    // Only for the logger of security channel:
    if ( $logger->getName() === Channels::SECURITY ) {
        $security_handler = new SomeSecurityHandler( /* some args */ ); 
        $security_handler->pushProcessor( $some_processor ); // Add handlers processor.

        $logger->pushHandler( $security_handler );  // Add the security handler to the logger.
    }
} );
```

The above code has the **exact same effect** as the following:

```php
$some_handler = new SomeHandler( /* some args */ );
$security_handler = new SomeSecurityHandler( /* some args */ );

Wonolog\bootstrap()
    ->use_handler( $some_handler, [], 'some_handler' )
    ->use_handler( $security_handler, [ Wonolog\Channels::SECURITY ], 'security_handler' )
    ->use_processor( 'My\App\loggers_processor' )
    ->use_processor_for_handlers( 'My\App\handlers_processor', [ 'some_handler', 'security_handler' ] );
```

The above code surely more concise and probably preferable, but can only be used in the MU plugin that bootstraps Wonolog.

Moreover, the controller API method necessitate the security handler to be instantiated on bootstrap, no matter if it will be used or not.
This is not an issue if instantiation of the object is trivial (as it probably should be), but if that's not the case, the **'wonolog.logger'** action can be used to perform just-in-time logger configuration.


### Configuring Handlers via Hooks

When using `use_handler()` in the MU plugin where Wonolog is bootstrapped, there's the possibility to configure the handler as necessary.

But there might be cases in which we need to configure a handler that was added using `use_handler()`, but outside the plugin that bootstraps Wonolog.

That's possible thanks to the **'wonolog.handler-setup'** action.

This action is triggered by Wonolog just before the first time an handler is used.

The handler is passed as first argument, and can be configured as needed.

The second argument contains the handler ID that was used with `use_handler()`.
If no handler ID was used (it is optional), the second argument will contain an ID calculated via `spl_object_hash()` that guarantees uniqueness, but unfortunately is not predicable.

Finally, the third argument contains an instance of thw `ProcessorsRegistry` class.
This object allows to "find" processors that where added via controller methods `use_processor()` and `use_processor_for_handlers()`.

For example, let's assume Wonolog bootstrapping was something like the following:

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

There are two handlers added for all the loggers, and a processor added to only one of them, identified by its ID, `'some_handler'`.

Now let's assume that we want to add the same processor also for the handler with the ID `'another_handler'`, but for some reason we can't or don't want to edit the MU plugin where the code above is located.

In some other place, we could leverage the **'wonolog.handler-setup'** action, like this:

```php
add_action( 'wonolog.handler-setup', function( HandlerInterface $handler, $handler_id, ProcessorsRegistry $registry ) {

    if ( $handler_id === 'another_handler' ) {
        $handler->pushProcessor( $registry->find( 'my_custom_processor' ) );
    }
}, 10, 3 );
```

The code above pushes the same processor that was added via `use_processor_for_handlers()` finding it by its ID.

This relies on the fact that the the processor ID argument was used (it is optional).
It's worth noting, however, that in some cases it could be possible to guess the processor ID even when it was not passed to `use_processor_for_handlers()`.

In fact, a processor is a PHP `callable`, which can be a string (i.e., a function name), an arrays (i.e., a static or a dynamic object method) or an object (i.e., a closure, or an invokable object).

When processors are functions, their processor ID, if not provided, is assumed by Wonolog to be the function name itself.

When processors are static methods, their processor ID, if not provided, is assumed to be the a string in the form `'Fully\Qualified\ProcessorClassName::methodName'`.

When processors are dynamic methods, if processor ID was not provided, it is calculated by Wonolog using `spl_object_hash()` that is not predictable and so, in those cases, the only chance to find the registered processor inside **'wonolog.handler-setup'** is that a custom processor ID was passed as third argument to `use_processor()` or `use_processor_for_handlers()`.


-------

Read next:

- [06 - Custom Hook Listeners](06-custom-hook-listeners.md) to see a complete example of a custom hook listener, its integration in Wonolog, and all the things that you need to know in order to write reusable Wonolog extensions.

Read previous: 

- [04 - Hook Listeners](04-hook-listeners.md) to read about hook listeners, the powerful feature of Wonolog that allows for logging any WordPress code.
- [03 - A Deeper Look at Wonolog](03-a-deeper-look-at-wonolog.md) to learn more advanced concepts and features of Wonolog.
- [02 - Basic Wonolog Concepts](02-basic-wonolog-concepts.md) to learn the basics of logging with Wonolog.
- [01 - Monolog Primer](01-monolog-primer.md) to learn a bit more about Monolog core concepts.

-------

[< Back to Index](https://github.com/inpsyde/Wonolog/)
