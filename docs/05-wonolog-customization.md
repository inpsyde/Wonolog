# Wonolog customization

## Table of Contents

- [Safe Wonolog bootstrap](#safe-wonolog-bootstrap)
- [Programmatically disable Wonolog](#programmatically-disable-wonolog)
- [Bootstrap configuration](#bootstrap-configuration)
	- [Wonolog default handler](#wonolog-default-handler)
		- [Default handler filter hooks](#default-handler-filter-hooks)
		- [Replace the default handler](#replace-the-default-handler)
	- [Bootstrap flags](#bootstrap-flags)
- [The `Controller` API](#the-controller-api)
	- [Customize default handler](#customize-default-handler)
	- [Add more handlers](#add-more-handlers)
		- [Handler id](#handler-id)
	- [Customize default processor](#customize-default-processor)
	- [Add more processors](#add-more-processors)
		- [Add processors to loggers](#add-processors-to-loggers)
		- [Add processors to handlers](#add-processors-to-handlers)
		- [Processor id](#processor-id)
	- [Customize default hook listeners](#customize-default-hook-listeners)
- [Loggers and handlers hooks](#loggers-and-handlers-hooks)
	- [Configure loggers via hooks](#configure-loggers-via-hooks)
	- [Configure handlers via hooks](#configure-handlers-via-hooks)
	

## Safe Wonolog bootstrap

To be initialized Wonolog needs the function `Inpsyde\Wonolog\bootstrap()` to be called in a MU plugin.

In many cases, it is safe enough to just do it, because following suggested practice to install Wonolog via Composer at 
website level, when the mu plugin is ran, Wonolog will always be there.

However, one might want to check for function existence before calling it.

```php
if ( function_exists( 'Inpsyde\Wonolog\bootstrap' ) ) {
  Inpsyde\Wonolog\bootstrap();
}
```

While this works and it's not that bad, the suggested practice is not to check function existence, but check existence of 
`Inpsyde\Wonolog::LOG` constant instead:

```php
if ( defined( 'Inpsyde\Wonolog\LOG' ) ) {
  Inpsyde\Wonolog\bootstrap();
}
```

That constant is defined in the same file of the bootstrap function, and there's not way constant exists but function doesn't. 

Considering that checking a constant is faster than checking a function (constant is even defined at compile time, allowing 
for opcode cache optimization) and considering that such code will run on every single request to your website, we suggest 
this practice as it is a good compromise between safety and performance.


## Programmatically disable Wonolog

When (reasonably) the same code is deployed to all environments it is not possible to enable or disable Wonolog per 
environment (not being a plugin, no way to _deactivate_ it).

However, it is a quite common practice disable logging on some environments, and this is why Wonolog supports an environment 
variable that can be used to turn it off completely, it is `WONOLOG_DISABLE`.

For example, by doing:

```php
putenv("WONOLOG_DISABLE=TRUE")
```

Wonolog will do nothing, no matter if `Inpsyde\Wonolog\bootstrap()` is called and how.

Sometimes, may be necessary to enable or disable logging based on other context. In those cases, Wonolog offers a filter, 
`'wonolog.enable'`, that can be used for the scope:

```php
add_filter( 'wonolog.disable', 'My\App\should_logging_be_disabled' )
```

or

```php
add_filter( 'wonolog.disable', '__return_true' )
```

to always disable Wonolog... but would probably be better to don't call `Inpsyde\Wonolog\bootstrap()` (or comment it out), 
if that's the desired result.

The filter will receive a single argument that is initially set according to the value of `WONOLOG_DISABLE` environment 
variable.


## Bootstrap configuration


### Wonolog default handler

When bootstrapping Wonolog with all the defaults the most notably thing that happen is probably the fact that a default 
handler is attached to the loggers of all channels.

When no handler is added, Monolog uses an handler to all loggers that just dump the content of log record to "standard output".

This choice makes sense for a library like Monolog that leave to implementers decide how logs should be handled by default, 
but being Wonolog a project-specific package (it targets WordPress and nothing else) we thought that to provide out-of-the-box a *persistent* default handler made sense.

As mentioned in the "Getting started section", Wonolog uses a default handler built around Monolog [`StreamHandler`](https://github.com/Seldaek/monolog/blob/master/src/Monolog/Handler/StreamHandler.php) 
that writes all the logs to file whose path change based on record date.

The default root path for the log files is `{WP_CONTENT_DIR}/wonolog/` and inside that, files are saved to a path formed 
using the format: `{Y/m/d}.log`.

**Please note: using content folder to store logs is NOT a good idea**.

In fact, logs very likely contain sensitive data that in content folder are publicly accessible and **that is in best 
case a privacy leakage issue, in worst case a security threat**.

However, WordPress has no place custom code is intended to save files that must not be public. So using a subfolder of 
content directory is the only option we had to use as default.

Don't worry, everything that regards the default handler is very configurable, in more than one way, and **it is highly 
recommended to change the default handler root path to a folder that is not publicly accessible**.

That can be done by:

- setting `WONOLOG_DEFAULT_HANDLER_ROOT_DIR` environment variable to the desired path
- using the `'wonolog.default-handler-folder'` filter to return the desired path. Callbacks attached to the filter will 
initially receive the value of `WONOLOG_DEFAULT_HANDLER_ROOT_DIR`  if set, or `{WP_CONTENT_DIR}/wonolog/` full path when 
the environment variable is not set.

There are different other configurations possible for Wonolog default handler, all available via filter hooks.


#### Default handler filter hooks

The filter **`wonolog.default-handler-filename`** allows to edit the file name format. By default its value is `'{date}.log'` 
where `{date}` is replaced by the value returned by PHP `date()` function, using the default date format or the custom one 
returned by callbacks attached to `'wonolog.default-handler-date-format'` filter hook. Note that `{date}` is required to 
be part of filename format.

The filter **`wonolog.default-handler-date-format`** allows to change the default date format, that is `'Y/m/d'`. 
The result of PHP `date()` function used with this format, will be replaced in the filename format string, by default 
`'{date}.log'`, but liable to configuration thanks to `'wonolog.default-handler-filename'` filter hook.

The filter **`wonolog.default-handler-bubble`**  allows to configure the "bubble" property of default handler, by default `true`.
When an handler has bubble property set to false, the records it handles will not be propagated to other handlers. 

The filter **`wonolog.default-handler-use-locking`**  filter tells the Monolog `StreamHandler` used by Wonolog default 
handler to acquire exclusive lock on the log file to be written. Default value is `true`.


#### Replace the default handler

As seen in previous section, default handler is very configurable, but many times is desirable to have a completely custom 
handler (nothing that writes to files, for example).

Or it is even possible one wants to don't configure any default handler at all, but different handlers for different loggers.

All this is very possible with Wonolog.

Replace default handler is very easy: just create an instance of an object implementing `Monolog\Handler\HandlerInterface` 
(better if  extending `Monolog\Handler\AbstractProcessingHandler`) and pass it as first argument to `Inpsyde\Wonolog\bootstrap()` 
function.

For example, a mu plugin that configures Wonolog to use a New Relic handler as default handler might look like this:

```php
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

Each of this four operations is connected to a flag, that is a constant in the `Inpsyde\Wonolog` namespace.

They are:

- `USE_DEFAULT_HANDLER`
- `USE_DEFAULT_PROCESSOR`
- `LOG_PHP_ERRORS`
- `USE_DEFAULT_HOOK_LISTENERS`


The `bootstrap()` function accepts a bitmask of these constants as second argument, to allow to enable only some of the features.

The default value for the this bitmask argument is another flag, `USE_DEFAULT_ALL`, that equals to use a bitmask of all four flags.

There's yet another flag that is `USE_DEFAULT_NONE` that is what needs to be used to disable all of them.

For, example, with the following code:

```php
Wonolog\bootstrap( null, Wonolog\USE_DEFAULT_NONE );
```

Wonolog will be bootstrapped, but  none of the four default tasks will be performed and so no log record will be kept, 
unless further configuration is used.

Don't fear, `Wonolog\bootstrap()` function returns an instance of `Wonolog\Controller`: an object that provides an API to 
configure Wonolog according to any needs.


## The `Controller` API


### Log PHP errors

The flag `LOG_PHP_ERRORS` passed to `bootstrap()` function tell's Wonolog to use the Wonolog error (and exception) handler.

The same result can be obtained by calling **`log_php_errors()` ** on the controller object returned by `bootstrap()`.

```php
Wonolog\bootstrap( null, Wonolog\USE_DEFAULT_NONE )
	->log_php_errors();
```

Explicitly calling `log_php_errors()`, instead of using the flag, allows to pass an argument, that lets configure the types 
of errors that must be logged by Wonolog.

For example:

```php
Wonolog\bootstrap( null, Wonolog\USE_DEFAULT_NONE )
	->log_php_errors( E_ALL ^ E_DEPRECATED );
```

The argument accepted by  `log_php_errors()` is the same of the second argument accepted by PHP 
[`set_error_handler`](http://php.net/manual/en/function.set-error-handler.php) function.


### Customize default handler

When `null` is passed as first bootstrap argument, and the flag `USE_DEFAULT_HANDLER` is not part of the flags bitmask no 
default handler will be initialized, which means that no log will be done unless some handlers are added.

The `Controller` object returned by `bootstrap()` provides a method **`use_default_handler()`** that can be used with no 
arguments telling Wonolog to use its default handler or can be used passing a custom handler to tell Wonolog to use it as 
default handler.

For example:

```php
Wonolog\bootstrap( null, Wonolog\USE_DEFAULT_NONE )
	->use_default_handler( new Handler\NewRelicHandler ( Logger::ERROR, true, 'my_new_relic_app' ) );
```

A note: if `USE_DEFAULT_HANDLER` flag is used in the `bootstrap()` function, or when an handler instance is passed as first 
argument to it, calling `use_default_handler()` on the returned `Controller` object will do nothing, because the default 
handler is already set by `bootstrap()`.


### Add more handlers

One of the things that makes Monolog so flexible is the possibility to have more handlers for each channel.

Based on its log level, each record will then be handled by loggers that declare a compatible minimum level, until there's 
no more handlers or one of them stop to "bubble" the record to subsequent handlers.

However, as of now, we only seen how to add the "default" handler, that will be added to _all_ loggers, so it is time to 
see how additional handlers can be added.

The controller object returned by `bootstrap()` has a method, **`use_handler()`** for the scope.

Its first and only mandatory argument must be an instance of a Monolog handler.

Using the method with only one argument, will add the given handler to all the loggers.

As a second argument, it is possible to pass an array of channel names, to tell Wonolog to use the handler only for loggers 
assigned to specific channels.

For example, the following could be the entire mu-plugin code necessary to configure Wonolog:

```php
use Inpsyde\Wonolog;
use Monolog\Handler\NativeMailerHandler;
use Monolog\Logger;

if ( ! defined('Inpsyde\Wonolog\LOG') ) {
  return;
}

// Tell default handler to use given folder for logs
putenv( 'WONOLOG_DEFAULT_HANDLER_ROOT_DIR=/etc/logs' );

$email_handler = new NativeMailerHandler(
  'alerts@example.com',              // to
  'Security alert from example.com', // subject
  'logs@example.com',                // from
  Logger::ERROR                      // minimum level
);

Wonolog\bootstrap()->use_handler( $email_handler, [ Wonolog\Channels::SECURITY ] );
```

The snippet above, tells Wonolog to use the default Wonolog handler for all loggers, and via environment variable tells 
default handler where to save log files.

Moreover, for the channel `Channels::SECURITY` (one of the five default Wonolog channels) an handler is setup to send log 
records with a minimum level of `Logger::ERROR` via email. The handler is provided by Monolog.


##### Handler id

There's an additional optional arguments that `use_handler()` takes, it is `$handler_id`. 

When provided, this id can be used to uniquely identify an handler in Wonolog.

For example, as explained below in [Add more processors](#add-more-processors) section, Wonolog allows to add processors 
to specific handlers, and to be able to identify handlers for the scope it is necessary to know the handler id.

Another reason to use handler id is that during its execution Wonolog triggers some hooks passing handlers as hook argument 
for further configuration. In those hooks the given handler id will also be passed as hook argument, allowing to uniquely 
distinguish handlers inside hook callbacks.


### Customize default processor

When using `USE_DEFAULT_PROCESSOR` as part of the flags bitmask passed to `bootstrap()` Wonolog will initialize its default 
processor (that adds WordPress context information to each log record).

The same result can be obtained by calling **`use_default_processor()`** on the controller returned by `bootstrap()`.

Passing a custom processor (that is any callable) to `use_default_processor()` is possible to tell Wonolog to use it as 
default processor instead of shipped default processor.

For example:

```php
Wonolog\bootstrap( null, Wonolog\USE_DEFAULT_NONE )
  	->use_default_handler()
	->use_default_processor( [ new MyCustomRecordProcessor(), 'process' ] );
```

Note: as can be guessed in the snippet above, all the controller methods implements fluent interface, that is they return 
the same instance on which they are called, allowing to call further methods.


### Add more processors

In Monolog, processors are callbacks that receive the record array and return a (maybe) altered value before the record 
is passed to handlers.

Processors are used in Monolog at two different steps: **there are processors that are assigned to _loggers_ and processors 
that are assigned to _handlers_**.

Handler-specific processors performed by an handler have no effect on _other_ handler-specific processors, but processors 
assigned to loggers will have effect on all the records of a given channel.

The default Wonolog processor is assigned to the loggers of all channels, it means that it will have effect on all the log 
records processed by Wonolog.

The controller object returned by `bootstrap()` provides API to add processors to loggers or to handlers.


#### Add processors to loggers

**`Controller::use_processor()`** method is what needs to be used to add processors to some or all loggers.

The first and only required argument is a callback (anything that is `callable` in PHP) that will be used as processor.

If no other arguments are provided, the processor will be used for all channels (which means will affect all the log records, 
just like Wonolog default processor).

Passing as second argument an array of channels names, it is possible to tell Wonolog to use the processor only for loggers
of specific channels.

For example:

```php
Wonolog\bootstrap()
  ->use_processor( 'My\App\security_logs_processor', [ Wonolog\Channels::SECURITY ] );
```

The snippet above will add a custom function as processors for all the records belonging to security channel.


#### Add processors to handlers

**`Controller::use_processor_for_handlers()`** method is what needs to be used to add processors to some or all handlers.

The first and only required argument is a callback (anything that is `callable` in PHP) that will be used as processor.

Adding processors to all handlers, means they will affect all log records processed by Wonolog. However, note that according 
to the Monolog work-flow described above, processors for handlers will be processed _after_ processors for loggers.

Passing as second argument an array of handler ids, it is possible to tell Wonolog to use the processor only for loggers 
of specific handlers.

For example:

```php
Wonolog\bootstrap()
  ->use_handler( $some_handler, [], 'some_handler' )
  ->use_handler( $another_handler, [ Wonolog\Channels::SECURITY ], 'another_handler' )
  ->use_processor_for_handlers( 'My\App\custom_processor', [ 'some_handler', 'another_handler' ] );
```

The snippet above first adds two handlers, the first for all channels, the second only for security channel, finally it 
adds a processor to be used for those two handlers, referencing them by id.


#### Processor id

Both `use_processor()` and  `use_processor_for_handlers()` methods accept as third argument an unique id for the processor.

This will be passed as argument by some hooks triggered by Wonolog, and allows to distinguish processors inside hook callbacks. 

More on this in the [Configure handlers via hooks](#configure-handlers-via-hooks]) section.


### Customize default hook listeners

The flag `USE_DEFAULT_HOOK_LISTENERS` tells Wonolog to use all the hook listeners shipped with Wonolog.

The same result can be obtained by calling **`use_default_hook_listeners()`** on the returned controller object.

Wonolog also offers the possibility to choose which listener to use, by using  **`use_hook_listener()`** on the controller, 
passing as argument an instance of the listener to be used. 

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

The above snippet tells Wonolog to use all default listeners, and actually equals to just call `use_default_hook_listeners()`, 
but of course it is possible only use just the desired listeners.

Note that the `use_hook_listener()` is also the method that needs to be used to tell Wonolog to use **custom listeners **.

In the file ["Custom hook Listeners"](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/06-custom-hook-listeners.md)
there's a complete example of a custom hook listener implementation and its integration in Wonolog.


## Loggers and handlers hooks

The API that controller provides should be enough to setup Wonolog for all the possible use cases.

However, the controller API is accessible only in the mu-plugin that bootstraps Wonolog. 

If for any reason some further configuration for handlers and processors is necessary, Wonolog provides hooks that allows 
loggers,  handlers and processors configuration form other places, very likely other MU plugins.


### Configure loggers via hooks

In the sections above is explained how `use_handler()`, `use_processor()` and `use_processor_for_handlers` controller 
methods can be used to add more handlers and processors to be used by Wonolog. 

The same operations can be done by using the hook **'wonolog.logger'**.

It is triggered just before the first time a logger is used, and  pass the logger as as argument. By exposing the Monolog 
object, it makes possible to use [Monolog API](https://github.com/Seldaek/monolog/blob/master/src/Monolog/Logger.php) 
(`Logger::push_handler()`, `Logger::push_processor()`) to add handlers and processors to the logger.

`Logger::getName()` method can be used to access the channel name inside hook callbacks, being allowed to add handlers 
and processors only for loggers of specific channels.

For example:

```php
add_action( 'wonolog.logger', function( Monolog\Logger $logger ) {
  
     $loggers_processor = 'My\App\loggers_processor';   // a processor for all loggers
     $handlers_processor = 'My\App\handlers_processor'; // a for handlers instantiated below
       
     $some_handler = // instantiate an handler here... 
     $some_handler->pushProcessor( $handlers_processor ); // add handler processor
     
     // add handler and processor to logger
     $logger
       ->pushHandler( $some_handler );
       ->pushProcessor( $loggers_processor );
     
     // only for the logger of security channel...
     if ( $logger->getName() === Channels::SECURITY ) {
       
       $security_handler = // instantiate another handler here... 
       $security_handler->pushProcessor( $some_processor ); // add handler processor
       
       $logger->pushHandler( $security_handler );  // add security handler to the logger
     }
} );
```

The snippet above has the **exact same effect of**:

```php
$some_handler = // instantiate an handler here... 
$security_handler = // instantiate another handler here... 

Wonolog\bootstrap()
  ->use_handler( $some_handler, [], 'some_handler' )
  ->use_handler( $security_handler, [ Wonolog\Channels::SECURITY ], 'security_handler' )
  ->use_processor( 'My\App\loggers_processor' )
  ->use_processor_for_handlers( 'My\App\handlers_processor', ['some_handler', 'security_handler'] );
```

The latter snippet is surely more concise and probably preferable, but can only be used in the mu-plugin that bootstraps 
Wonolog.

Moreover, the controller API method necessitate the security handler to be instantiated on bootstrap, no matter if it 
will be used or not. This is not an issue if instantiation of the object is trivial (as it probably should be), but if 
that's not the case, the **'wonolog.logger'** hook can be used to perform just in time logger configuration.


### Configure handlers via hooks

When using `use_handler()` in the MU plugin where Wonolog is bootstrapped, there's the possibility to configure the 
handler as necessary. 

But there might be cases in which we need to configure an handler that was added using `use_handler()`, but outside the 
plugin that bootstraps Wonolog.

That's possible thanks to the **'wonolog.handler-setup'**  hook.

This hook is triggered by Wonolog just before the first time an handler is used.

The handler is passed as hook first argument, and can be configured as needed. 

The hook passes as second argument the handler id that was used with `use_handler()` . If no handler id was used 
(it is optional) the second argument will contain an id calculated via `spl_object_hash() ` that guarantees uniqueness but 
unfortunately is not predicable.

Finally, the hook passes as third argument an instance of a class named `ProcessorsRegistry`. This object allows to "find" 
processors that where added via controller methods `use_processor()` and `use_processor_for_handlers()`.

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

Here there are two handlers added for all the loggers, and a processor added to only one of them, identified by its id
 (`'some_handler'`).

Now let's assume that we want to add the same processor also for the handler with id `'another_handler'`, but for some 
reason we can't or don't want to edit the MU plugin where code above is located.

In some other place, we could leverage  **'wonolog.handler-setup'**  hook for the scope, like this:

```php
add_action(
  'wonolog.handler-setup',
  function( HandlerInterface $handler, $handler_id, ProcessorsRegistry $processor_registry ) {
    if ( $handler_id === 'another_handler' ) {
      $handler->pushProcessor( $processor_registry->find( 'my_custom_processor' ) );
    }
  },
  10,
  3
);
```

The code above push the same processors that was added via `use_processor_for_handlers()` finding it by its id, that is 
what was passed as third argument to `use_processor_for_handlers()`.

This relies on the fact that the the processor id argument was used (it is optional). Worth noting, however that in some 
cases it could be possible to guess the processor id even when it was not passed to `use_processor_for_handlers()` .

In fact, processors are PHP `callable`, which can be strings (function names), arrays (static or dynamic object methods) 
or objects (closures, invokable objects).

When processors are functions, their processor id, if not provided, is assumed by Wonolog to be the function name itself.

When processors are static methods, their processor id, if not provided, is assumed to be the a string in the form 
`'Fully\Qualified\ProcessorClassName::methodName'`.

When processors are dynamic methods, if processor id was not provided, it is calculated by Wonolog using `spl_object_hash()` 
that is not predictable and so, in those cases, the only chance to find the registered processor inside **'wonolog.handler-setup'** 
is that a custom processor id was passed as third argument to  `use_processor` or `use_processor_for_handlers`.

-------

Read next:

- [06 - Custom hook listeners](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/06-custom-hook-listeners.md)

Read previous: 

- [04 - Hook listeners](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/04-hook-listeners.md)
- [03 - A Deeper look at Wonolog](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/03-a-deeper-look-at-wonolog.md)
- [02 - Basic Wonolog concepts](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/02-basic-wonolog-concepts.md)
- [01 - Monolog Primer](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/01-monolog-primer.md)

-------

[< Back to index](https://github.com/inpsyde/wonolog/)
