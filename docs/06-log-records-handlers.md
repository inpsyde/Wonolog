# Log records handlers

Wonolog will collect log records coming from different sources:

- WordPress core, via default hook listeners
- Plugins/themes/packages that perform log via logging action hooks registered as Wonolog hook alias
- Plugins/themes/packages that perform logs via PSR-3 making use of Wonolog PSR-3 logger
- Plugins/themes/packages that perform logs via custom hook listeners

None of the sources is aware of *how* the log records they produce are handled. The *how* is, in fact, determined by Wonolog configuration for the underlying Monolog handlers.

---

# Table of contents

- [From channel to handlers](#from-channel-to-handlers)
- [Configuring handlers](#configuring-handlers)
    - [About identifier](#about-identifier)
- [Fallback handler](#fallback-handler)
    - [Logs folder](#logs-folder)
    - [Logs file names](#logs-file-names)
    - [Minimum level](#minimum-level)
    - [Disabling the fallback handler](#disabling-the-fallback-handler)
- [About auto-calculated logs folder](#about-auto-calculated-logs-folder)
- [About auto-calculated minimum log level](#about-auto-calculated-minimum-log-level)

---



## From channel to handlers

Every log record, regardless the source, will have one and only channel. Based on the channel, Wonolog will decide how to handle the log record.

An handler is any object implementing [`Monolog\Handler\HandlerInterface` interface](https://github.com/Seldaek/monolog/blob/2.2.0/src/Monolog/Handler/HandlerInterface.php).

Thanks to the [dozen of ready-made Monolog handlers](https://seldaek.github.io/monolog/doc/02-handlers-formatters-processors.html#handlers), there are countless possibilities on how to persist log records, but it is also quite straightforward to [write a custom handler](https://seldaek.github.io/monolog/doc/04-extending.html).

In Wonolog handlers are organized in two groups:

- Channel-specific handlers: used to handle records having specific channels
- Generic handlers, that are used to handle all records, regardless channel

When Wonolog encounters a log record:

- get all the channel-specific handlers assigned to log record channel
- get all generic handlers, but excluding those that have been specifically excluded for the log record channel

Obtained the list of handlers, Wonolog passes the log record to each of them for handling.

It worth noting that each handler can decide to stop the propagation of log record handling to other handlers, by retuning `false` in their `handle()` method. Most of the ready-made Monolog handlers have a "bubbling" property that if set to `false` will make `handle()` return `false` and thus
preventing the same record to be handler by other handlers.



## Configuring handlers

To add/remove generic handlers it is necessary to use the `wonolog.setup` action and call, respectively, `pushHandler` or `removeHandler` methods on the given `Configurator` object.

To add/remove channel-specific handlers the `Configurator` methods to use are `pushHandlerForChannels` and `removeHandlerFromChannels`.

For example:

```php
add_action(
    'wonolog.setup',
    function (Inpsyde\Wonolog\Configurator $config) {
        $config
            ->pushHandler(new HandlerOne)
            ->pushHandler(new HandlerTwo)
            ->pushHandlerForChannels(new SmsHandler, 'SECURITY', 'URGENT')
            ->pushHandler(new SlackHandler)
            ->removeHandlerFromChannels(SlackHandler::class, 'SECURITY')
            ->removeHandler(HandlerTwo::class);
    }
);
```

Using the above configuration:

- the handler `HandlerOne` will be used for all logs
- the handler `SmsHandler` will be used only for logs in `SECURITY` and `URGENT` channels
- the handler `SlackHandler`  will be used for all logs, only excluding those in `SECURITY` channel
- the handler `HandlerTwo` will not be used, because it is removed after being added.

`Configurator` has two additional methods that help configuring the channel to handler mapping.
Those are:  `enableHandlerForChannels` and `enableHandlersForChannel`. Their scope is the same: assign existing handler(s) to existing channel(s).

Let's imagine, for example, that one MU plugin/package adds the configuration in the snippet above, *another* MU plugin/package could do:

```php
add_action(
    'wonolog.setup',
    function (Inpsyde\Wonolog\Configurator $config) {
        $config->enableHandlerForChannels(SmsHandler::class, 'IMPORTANT', 'SMS');
    }
);
```

The code above assign the `SmsHandler` to two additional channels besides the two channels that it was already assigned to.

The method `enableHandlersForChannel` works in a similar way, but has a different signature that takes a single channel as first parameter, and a variadic number of handler identifiers from second parameter.

### About the identifier

The methods `removeHandler`, `removeHandlerFromChannels`, `enableHandlerForChannels`, and `enableHandlersForChannel` all accept handler *identifiers*.

Identifiers are used to uniquely identify an handler, and can be passed to `pushHandler` as second parameter. When no custom identifier is passed, the fully-qualified class name of the added handler is used as identifier. That is the reason why the snippets above use the fully-qualified class name
to identify an handler.

The class-name-as-identifier strategy does not work if multiple handlers of the same class are purposely added, for example two `MailHandler ` that send email to two different email addresses. In that case it is necessary to pass explicitly an unique identifier as second parameter to `pushHandler`.



## Fallback handler

Wonolog needs *at least* one handler per channel, otherwise log records will be lost.

This is why Wonolog instantiates a "fallback" handler for all the channels that have none. For example, when Wonolog is just required without _any_ configuration the fallback handler is instantiated and assigned to all default channels (see [*"What is logged by default"*](03-what-is-logged-by-default.md) chapter for list of default channels).

The "fallback handler" is a custom handler that ships with Wonolog, and write logs to files, its class is `FileHandler` and it is a wrapper around Monolog `StreamHandler` designed to auto-tune its configuration to work well in WordPress context.

### Logs folder

`FileHandler` uses Monolog `StreamHandler` to write file. To determine the folder path, Wonolog looks at the [`WP_ERROR_LOG`](https://wordpress.org/support/article/debugging-in-wordpress/#wp_debug_log)
constant, and when it contains a path to a file, Wonolog uses that file's folder as the parent folder for its logs. For example, if `wp-config.php` contains something like the following:

```php
define('WP_DEBUG_LOG', '/tmp/wp-errors.log');
```

`FileHandler` will use  `/tmp/wonolog/` as parent folder for all its log files.

If `WP_DEBUG_LOG` is not defined or is a boolean `FileHandler` fallbacks to a `/wonolog` folder inside WordPress "upload" folder.

Considering that log files should **not** be publicly accessible, when `FileHandler` writes file in the "uploads" folder, it adds in its base folder a `.htaccess` file that prevents public access to it, but that only works if the web-server in use is Apache and it is configured to take into account `.htaccess` files.

That is why when using Wonolog fallback handler it is essential to make sure that either the path used is not publicly accessible.

To manually change the `FileHandler` base folder it is necessary to either define `WP_DEBUG_LOG` constant pointing to a file, as shown above, or alternatively call `withFolder()` method on it.

That means that we need to access the `FileHandler` instance for that.

The first obvious way is to manually instantiate `FileHandler` and push it in Wonolog via `Configurator::pushHandler()`. For example:

```php
use Inpsyde\Wonolog\{Configurator, DefaultHandler\FileHandler};

add_action(
    'wonolog.setup',
    function (Configurator $config) {
        $config->pushHandler(FileHandler::new()->withFolder('/logs/wp/'));
    }
);
```

However, that means that `FileHandler` is not anymore a "fallback" handler, because that is _generic_ handler added to all channels (so Wonolog does not need to create any fallback handler).

This might be totally fine, but might not. The alternative is to use the hook `wonolog.handler-setup` that is fired once for each handler being used. For example:

```php
add_action(
    'wonolog.handler-setup',
    function (Monolog\Handler\HandlerInterface $handler) {
        if ($handler instanceof Inpsyde\Wonolog\DefaultHandler\FileHandler) {
            $handler->withFolder('/logs/wp/');
        }
    }
);
```

### Logs file names

Regardless how the "base" folder is determined, inside it by default `FileHandler` writes daily files, with the format  `/{$year}/{$month}/{$day}.log`.

The default format can be changed calling `FileHandler::withDateBasedFileFormat()` method.
For example:

```php
add_action(
    'wonolog.handler-setup',
    function (Monolog\Handler\HandlerInterface $handler) {
        if ($handler instanceof Inpsyde\Wonolog\DefaultHandler\FileHandler) {
            $handler->withFolder('/logs/wp/')->withDateBasedFileFormat('Y-m-d');
        }
    }
);
```

By using configuration above, `FileHandler` will write log files like `/logs/wp/2021-05-26.log`.

Alternatively, there's the method `withFilename` that makes `FileHandler` use the same file name, this is useful to implement custom ways to calculate the file name.

### Minimum level

Many Monolog handlers have a "minimum log level", and they ignore any log below that level. For example, a handler that sends logs via SMS might have a minimum level of "critical" to don't disrupt anyone's phone (and serenity of life).

`FileHandler` has that. Its minimum level is, by default, calculated based on the value of the environment variable `WONOLOG_DEFAULT_MIN_LEVEL`. If that is not defined, Wonolog checks the value of the `WP_DEBUG_LOG` constant, and when that value is `false`, the default handler's minimum
level will be "warning", otherwise, it will be "debug".

To explicitly set default handler minimum level it is possible to use its `withMinimumLevel` method:

```php
add_action(
    'wonolog.handler-setup',
    function (Monolog\Handler\HandlerInterface $handler) {
        if ($handler instanceof Inpsyde\Wonolog\DefaultHandler\FileHandler) {
            $handler->withMinimumLevel(Inpsyde\Wonolog\LogLevel::ERROR);
        }
    }
);

```

Note how the minimum level is set using a Wonolog `LogLevel` class constant. The reason is that PSR-3 log levels have a string form, so it is not possible to determine which level is higher/lower programmatically. Wonolog `LogLevel` class constants “map” PSR-3 log levels to numeric values to
make comparison possible.

### Disabling the fallback handler

The fallback handler is an instance of `FileHandler` that Wonolog instantiates for those channels that have no other handlers attached, to ensure no log is lost.

It might be desirable to disable this behavior, so those log records having a channel not assigned to any handler will not be handled.

```php
add_action(
    'wonolog.setup',
    function (Inpsyde\Wonolog\Configurator $config) {
        $config->disableFallbackHandler();
    }
);
```

Alternatively, it is possible to disable the fallback handler only for specific channels:

```php
add_action(
    'wonolog.setup',
    function (Inpsyde\Wonolog\Configurator $config) {
        $config->disableFallbackHandlerForChannels('DEBUG', 'SOME_PLUGIN');
    }
);
```



## About auto-calculated logs folder

When using in Wonolog a Monolog handler that writes files it might be desired to calculate the logs folder in the same way used by `FileHandler`, including the creation of `.htaccess` file inside the folder to prevent public access.

That is possible thanks to the `Inpsyde\Wonolog\LogsFolder::determineFolder()` static method, the same internally used by `FileHandler`. The method optionally accepts a custom folder path as parameter, in that case the method ensures the given folder is created (with proper access rights) and the `.htaccess` file is placed inside it, in the case it is a subfolder of WP content or WP uploads folder.



## About auto-calculated minimum log level

Many times when integrating Monolog handler for Wonolog it is desired to use the same log level used by `FileHandler`, that is the one based on `WONOLOG_MIN_LEVEL` environment variable or `WP_DEBUG_LOG` constant.

In that case it is possible to use `Inpsyde\Wonolog\LogLevel::defaultMinLevel()` static method, the same `FileHandler` uses internally to determine the minimum level to use when none is explicitly configured.




---

1. [Introduction](./00-introduction.md)
2. [Anatomy of a Wonolog log record](./01-anatomy-of-a-wonolog-log-record.md)
3. [Bootstrap and configuration gateway](./02-bootstrap-and-configuration-gateway.md)
4. [What is logged by default](./03-what-is-logged-by-default.md)
5. [Designing packages for Wonolog](./04-designing-packages-for-wonolog.md)
6. [Logging code not designed for Wonolog](./05-logging-code-not-designed-for-wonolog.md)
7. **Log records handlers**
8. [Log records processors](./07-log-records-processors.md)
9. [Custom PSR-3 loggers](./08-custom-psr-3-loggers.md)
10. [Configuration cheat sheet](./09-configuration-cheat-sheet.md)

---

« [Logging code not designed for Wonolog](./05-logging-code-not-designed-for-wonolog.md) || [Log records processors](./07-log-records-processors.md) »
