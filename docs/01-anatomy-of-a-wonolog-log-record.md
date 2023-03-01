# Anatomy of a Wonolog log record

Wonolog is compatible with the [PSR-3 standard](https://www.php-fig.org/psr/psr-3/), and provides a
PSR-3 implementation.


---

# Table of contents

- [The structure of PSR-3 log events](#the-structure-of-psr-3-log-events)
- [Filling PSR-3 missing details](#filling-psr-3-missing-details)
    - [Comparable levels](#comparable-levels)
    - [Channels](#channels)
- [`LogData` object](#logData-object)

---

## The structure of PSR-3 log events

PSR-3 does not have a definition for log record objects, but only for "logger" objects, so it defines the standard for the log "emitter", but not for the *subject* of logging.

However, we can deduce what the subject is by looking at the PSR-3 logger primary method `log`:

```php
/**
 * @param mixed $level
 * @param string $message
 * @param array $context
 * @return void
 */
public function log($level, $message, array $context = array())
```

So we have:

- a “level” that defines the “severity” of the log event. Even if the “level” parameter is “mixed”, so it could be anything, PSR-3
  defines [a LogLevel class](https://www.php-fig.org/psr/psr-3/#5-psrlogloglevel) that enumerates (as class constants) a set of levels supported by the standard.
- a “message” that is a human-readable description of what the log event is about
- an optional “context” that is an arbitrary set of data that provides more information about the log event

## Filling PSR-3 missing details

PSR-3 was purposely designed to be widely compatible. That’s why many decisions were not taken in the standard and left to implementations.

### Comparable levels

PSR-3 inherits log levels from [RFC 5424](http://tools.ietf.org/html/rfc5424), which defines the levels' names and when to use each level. However, while RFC 5424 associated to each level a "numerical code" that makes them easily comparable by machines, PSR-3 doesn't do that but only defines levels in a human-readable string form.

For example, it is immediate for human beings to understand that a `CRITICAL` event is more severe than a `NOTICE` one, but that is not immediate for machines.

To fill this “missing detail” in PSR-3, Wonolog implements a “map” of PSR-3 levels to more machine-friendly numerical values, which looks like this:

| PSR-3 level | Wonolog level |
|-------------|---------------|
| DEBUG       | 100           |
| INFO        | 200           |
| NOTICE      | 250           |
| WARNING     | 250           |
| ERROR       | 400           |
| CRITICAL    | 500           |
| ALERT       | 550           |
| EMERGENCY   | 600           |

In all places where it requires a log level, unless differently specified (for example, via PHP type declaration), Wonolog can take either a string PSR-3 log level or an integer Wonolog log level.

### Channels

PSR-3 doesn't include any "meta" information for log records. In real applications, though, not all log records are treated equally. Administrators likely want to treat log records about relevant security issues differently from informational log records suitable for marketing purposes.

It's not only a matter of "severity". For example, suppose log records are sent via email. In that case, we can easily imagine how log records about security should not be sent to the same email address used for log records useful for marketing.

Wonolog makes use of the concept of "**log channels**". To speak WordPress jargon, if log records would be posts, "channel" would be their taxonomy. In Wonolog, **every log record has one and only one channel** that describes the "category" it belongs to.

It is worth mentioning here that, because Wonolog logs WordPress events out of the box, it needs to define channels for those events. For that, Wonolog defines six channels:

- HTTP
- DB
- SECURITY
- DEBUG
- CRON
- PHP-ERROR

When logging non-core events, it is possible to use any of these or to define new channels.

It is also important to mention that Wonolog uses one of these channels (by default "DEBUG") when it can't otherwise determine the log record channel. This "fallback" channel is referred to in Wonolog as the "default" channel (and can be customized).

## `LogData` object

The "additions" that Wonolog does to PSR-3 are captured in an interface for log records. Internally, Wonolog code does not refer to "message", "level", "context", and "channel" separately, but it makes use of an interface that combine all of these things in a single object. That interface
is `Inpsyde\Wonolog\Data\LogData` and it looks like this:

```php
namespace Inpsyde\Wonolog\Data;

interface LogData
{
    public function level(): int;

    public function message(): string;

    public function channel(): string;

    public function context(): array;
}
```

Dealing with this object Wonolog:

- enforces the usage of numeric levels, facilitating the handling of log records by machines
- enforces the presence of a single channel

Wonolog also ships with several implementations of the `LogData` interface. Among those, it is worth to mention:

- `Inpsyde\Wonolog\Data\Log`, which provides a few named constructors to build Wonolog log record objects from different "sources", like `Throwable` objects, `WP_Error` objects, or arbitrary arrays.
- eight level-specific implementations, like `Inpsyde\Wonolog\Data\Alert`, `Inpsyde\Wonolog\Data\Error`, etc., that accepts in constructor all `LogData` properties but `level`, which is hardcoded.

`LogData` interface and its implementations are only relevant when coding hook listeners as a "compatibility layer" for code that is not compatible with Wonolog: hook listeners are coupled with Wonolog and are required to use the Wonolog "log objects".

Plugin/themes/packages should **not** be aware of them, and either emit package-specific logging action hooks or implement PSR-3 `LoggerAware` to accept PSR-3 loggers.



---

1. [Introduction](./00-introduction.md)
2. **Anatomy of a Wonolog log record**
3. [Bootstrap and configuration gateway](./02-bootstrap-and-configuration-gateway.md)
4. [What is logged by default](./03-what-is-logged-by-default.md)
5. [Designing packages for Wonolog](./04-designing-packages-for-wonolog.md)
6. [Logging code not designed for Wonolog](./05-logging-code-not-designed-for-wonolog.md)
7. [Log records handlers](./06-log-records-handlers.md)
8. [Log records processors](./07-log-records-processors.md)
9. [Custom PSR-3 loggers](./08-custom-psr-3-loggers.md)
10. [Configuration cheat sheet](./09-configuration-cheat-sheet.md)

---

« [Introduction](./00-introduction.md) || [Bootstrap and configuration gateway](./02-bootstrap-and-configuration-gateway.md) »
