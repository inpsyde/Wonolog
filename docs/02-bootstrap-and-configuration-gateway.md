# Bootstrap and configuration gateway

Wonolog v2 bootstrap itself. As soon as it is required in a project using Composer, even without any configuration, it starts working right away using its default settings.

Wonolog v1 required a `bootstrap()` function to be called from a MU plugin, but that is not necessary anymore. That is probably the most significant user-facing change from v1 to v2.

Nevertheless, most of the time, *some* configuration is needed. MU plugins, even in v2, are still the place where configuration goes.



## The setup hook

Wonolog configuration is done by adding a callback to the **`wonolog.setup`** hook. This function passes an instance of the object `Inpsyde\Wonolog\Configurator` that is the gateway to all the possible Wonolog configuration.

For example:

```php
add_action(
    'wonolog.setup',
    funtion (Inpsyde\Wonolog\Configurator $config) {
        $config->doNotLogPhpErrors();
    }
);
```

This new approach was introduced to favor Wonolog configuration from different MU plugins (or early loaded Composer packages) and thus favor re-usable packages that configure various aspects of Wonolog.

In v1, the necessity to call a `bootstrap()` function favorited the presence of _a_ single place for configuration, usually copy-and-pasted from project to project.



### Wonolog writes logs

As soon as it is required in a project using Composer, even without any configuration at all, Wonolog starts working right away using its default settings. It means that Wonolog not only provides abstractions and APIs for plugins/themes/packages to log *their* stuff but also *writes* logs for WordPress core "events". The "*What is logged by default*" chapter explains which those "events" exactly are.

### Monolog

Anyway, to write logs, Wonolog needs a PSR-3 *implementation*, not just *abstraction*, and it uses [Monolog](https://seldaek.github.io/monolog/), the most popular PSR-3 implementation.

In Monolog, logs are written using “handlers”. Each handler is free to do anything with a log entry, and multiple handlers might process each log entry.

The nice thing about Monolog is that there are [dozens of ready-made handlers](https://seldaek.github.io/monolog/doc/02-handlers-formatters-processors.html#handlers) that, for example, write log to files, Syslog, databases, send emails or alerts, connect to logging services, and so on.

### Wonolog default handler

Because Wonolog needs at least one handler to start writing logs without any configuration, it instantiates a "default handler" that writes logs to files. 

Wonolog default handler creates daily log files in the path `/{$year}/{$month}/{$day}.log` under a folder that is determined by default by Wonolog. Without any configuration the containing folder is a `/wonolog` folder inside WordPress "uploads" folder.

Considering that logs should **not** be publicly accessible, Wonolog adds to that folder an `.htaccess` file that prevents access, but that only work if the web-server in use is Apache and it is configured to take into account .htaccess files.

That is why it is essential to make sure that, when using Wonolog default handler, the folder that contains all logs is a folder that is not publicly accessible.

#### Default handler folder

When the constant [`WP_ERROR_LOG`](https://wordpress.org/support/article/debugging-in-wordpress/#wp_debug_log) contains a path to a file, Wonolog uses that file folder as the parent folder for its logs. For example, if `wp-config.php` contains something like:

```php
define('WP_DEBUG_LOG', '/tmp/wp-errors.log');
```

Wonolog will write logs file in paths like `/tmp/wonolog/2021/09/30.log`, and that is without the need of any additional configuration.

If `WP_DEBUG_LOG` is not defined or is a boolean, the only way to change the Wonolog default handler folder is to instantiate and configure it. For example:

```php
use Inpsyde\Wonolog\{Configurator, DefaultHandler};

add_action(
    'wonolog.setup',
    funtion (Configurator $config) {
        $config->useAsDefaultHandler(DefaultHandler::new()->withFolder('/logs/wp/'));
    }
);
```

`DefaultHandler` class has some more configuration possibilities, e.g. it is possible to change the log file names.

Please note how `useAsDefaultHandler` can be used with **any** Monolog handler.

#### Default handler minimum level

Many Monolog handlers have a "minimum log level", and they ignore any log below that level. For example, a handler that sends logs via SMS might have a minimum level of "critical" to don't disrupt anyone's phone (and serenity of life).

Wonolog default handler has that. Its minimum level is, by default, calculated based on the value of the environment variable `WONOLOG_DEFAULT_MIN_LEVEL`. If that is not defined, Wonolog checks the value of the `WP_DEBUG_LOG` constant, and when that value is `false`, the default handler's minimum level will be "warning"; otherwise, it will be "debug".

To explicitly set default handler minimum level it is possible to use its `withMinimumLevel` method:

```php
add_action(
    'wonolog.setup',
    funtion (Inpsyde\Wonolog\Configurator $config) {
        $config->useAsDefaultHandler(
            Inpsyde\Wonolog\DefaultHandler::new()
                ->withFolder('/logs/wp/')
                ->withMinimumLevel(Inpsyde\Wonolog\LogLevel::ERROR)
        );
    }
);
```

Note how the minimum level is set using a Wonolog `LogLevel` class constant. The reason is that PSR-3 log levels have a string form, so it is not possible to determine which level is higher/lower programmatically. Wonolog `LogLevel` class constants “map” PSR-3 log levels to numeric values to make comparison possible.

#### Why default handler, and how to disable it

Wonolog ships a default handler in the first place because when there’s no configuration, Wonolog needs “a” handler to use.

Even when there’s some configuration, the concept of “default handler” is still important, no matter if that is an instance of `Wonolog\DefaultHandler` or any other Monolog handler, and the reason is that not having a default handler, some log entry might be “lost”.

In Wonolog, each log has a channel, and a channel *might* be assigned to one or more handlers. That means that the log entry “channel” determines how the log is actually “handled”.

When no handler is explicitly assigned to a log entry channel, Wonolog will default to its default handler, ensuring all log entries are somehow handled. Consequently, if there’s no default handler, any log having a channel without any assigned handler would not be handled at all.

```php
add_action(
    'wonolog.setup',
    funtion (Inpsyde\Wonolog\Configurator $config) {
        $config->disableDefaultHandler();
    }
);
```



### About auto-calculated log level

It is important to note that the “calculation” of minimum log level based on `WONOLOG_MIN_LEVEL` environment variable or `WP_DEBUG_LOG` constant only affects the Wonolog DefaultHandler.

Using a different handler (either as default handler or as channel-specific handler), it is responsibility of who adds the handler to configure its minimum level. In the case it is desired to use the same calculation applied by `DefaultHander`, it is possible to use `Inpsyde\Wonolog\LogLevel::defaultMinLevel()` static method, the same `DefaultHandler` uses internally to determine the minimum level to use when none is explicitly configured.