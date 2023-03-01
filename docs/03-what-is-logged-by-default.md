# What is logged by default

When Wonolog is required in a WordPress projects, it right away starts logging "things". Here we'll see *what* it logs.


---

# Table of contents

- [Default hook listeners](#default-hook-listeners)
    - [Configure enabled default listeners](#configure-enabled-default-listeners)
    - [About minimum logged level](#about-minimum-logged-level)

---

## Default hook listeners

When Wonolog is used to log "things" done in code that is not designed to be compatible with Wonolog (nor PSR-3), we need a "compatibility layer" that "listen to" WordPress hooks and make use of Wonolog API to store log records.

WordPress core is, of course, not natively compatible with Wonolog, and this is why Wonolog implements a "compatibility layer" for WordPress core. That is made up with a few "hook listeners" that "listen to" WordPress hooks and use Wonolog to perform logs.

Here's below the list of hook listeners Wonolog ships with and what they do:

| Hook listeners         | What it logs                                                                                                                                                               | PSR-3 log level                                                                                                                                                           | Wonolog "channel"                                                         |
|------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------|---------------------------------------------------------------------------|
| `CronDebugListener`    | It logs *every* performed WP cron, including time spent for it.                                                                                                            | `LogLevel::INFO`                                                                                                                                                          | "CRON"                                                                    |
| `DbErrorListener`      | Logs any error that occurs querying WP database                                                                                                                            | `LogLevel::ERROR`                                                                                                                                                         | "DB"                                                                      |
| `FailedLoginListener`  | Logs failed logins when same user attempts logging-in more than twice in a row. Useful to individuate brute force attacks. Log record "context" include remote IP address. | Variable. It depends on the number of attempts within 5 minutes. Start at `LogLevel::NOTICE` for 3 to 99 attempts, up to `LogLevel::CRITICAL` for more than 990 attempts. | "SECURITY"                                                                |
| `HttpApiListener`      | Logs two things:<br />1) erroneous responses obtained via WordPress HTTP API<br />2) HTTP request made to trigger WP crons                                                 | `LogLevel::ERROR` for erroneous HTTP responses.<br />`LogLevel::DEBUG` for HTTP cron requests.                                                                            | "HTTP" for erroneous HTTP responses.<br />"DEBUG" for HTTP cron requests. |
| `MailerListener`       | Logs two things:<br />1) debug messages from `PHPMailer` library used by WP<br />2) Errors triggered by WordPress for failure in sending emails.                           | `LogLevel::DEBUG` for `PHPMailer` debug output.<br />`LogLevel::ERROR` for failure in sending emails.                                                                     | "HTTP"                                                                    |
| `QueryErrorsListener`  | Logs any error in main WP Query, that is, every time `$wp_query->is_error()` returns true.                                                                                 | `LogLevel::DEBUG`                                                                                                                                                         | "HTTP" <br />(most of the times it's about HTTP errors, e. g. 404 errors) |
| `WpDieHandlerListener` | Listen to `wp_die` calls coming from `$wpdb` (that's the only way to catch DB connection or other "early" DB errors).                                                      | `LogLevel::CRITICAL`                                                                                                                                                      | "DB"                                                                      |

### Configure enabled default listeners

The default hook listeners listed above are all enabled by default but can be disabled, either at once or individually.

To configure which listeners are enabled (just like pretty much all Wonolog configuration), it is necessary to use the `wonolog.setup` hook and act on the passed `Configurator` instance. For example:

```php
use Inpsyde\Wonolog\{Configurator, HookListener};

add_action(
    'wonolog.setup',
    function (Configurator $config) {
        $config->disableDefaultHookListeners(
            HookListener\FailedLoginListener::class,
            HookListener\MailerListener::class
        );
    }
);
```

`Configurator::disableDefaultHookListeners` takes a variadic number of default listeners fully-qualified class names. To disable all default listeners there's `Configurator::disableAllDefaultHookListeners()`.

### About minimum logged level

It must be noted that out-of-the-box Wonolog determines its “minimum level” for logging based on the value of the environment variable `WONOLOG_DEFAULT_MIN_LEVEL`. If that is not defined, Wonolog checks the value of the `WP_DEBUG_LOG` constant, and when that value is false, the default minimum level will be “warning”, otherwise, it will be “debug” (more details in the [*"Bootstrap and configuration gateway"*](./02-bootstrap-and-configuration-gateway.md) chapter).

In the case some events listed above are not logged as expected, a probable cause is that the environment variable `WONOLOG_DEFAULT_MIN_LEVEL` is not set, and the `WP_DEBUG_LOG` constant is either not defined or defined to false.

---

1. [Introduction](./00-introduction.md)
2. [Anatomy of a Wonolog log record](./01-anatomy-of-a-wonolog-log-record.md)
3. [Bootstrap and configuration gateway](./02-bootstrap-and-configuration-gateway.md)
4. **What is logged by default**
5. [Designing packages for Wonolog](./04-designing-packages-for-wonolog.md)
6. [Logging code not designed for Wonolog](./05-logging-code-not-designed-for-wonolog.md)
7. [Log records handlers](./06-log-records-handlers.md)
8. [Log records processors](./07-log-records-processors.md)
9. [Custom PSR-3 loggers](./08-custom-psr-3-loggers.md)
10. [Configuration cheat sheet](./09-configuration-cheat-sheet.md)

---

« [Bootstrap and configuration gateway](./02-bootstrap-and-configuration-gateway.md) || [Designing packages for Wonolog](./04-designing-packages-for-wonolog.md) »
