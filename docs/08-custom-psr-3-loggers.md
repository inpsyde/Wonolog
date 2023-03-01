# Custom PSR-3 loggers

Wonolog _provides_ a PSR-3 implementation. That means that plugin/themes/packages can rely on the PSR-3 interfaces and "wait" for Wonolog to provide the implementation.

Consequently, plugins/themes/package does *not* need to ship any PSR-3 logger implementation.

Unfortunately, WordPress has no core mechanism for dependency injection, but developers can get around the issue in several ways, the simplest of which is a filter hook (as shown in the example in the [*"Designing packages for Wonolog"*](./04-designing-packages-for-wonolog.md) chapter).

However, there might be cases in which a specific PSR-3 *implementation* must be used.

---

# Table of contents

- [MU plugin example](#mu-plugin-example)
- [Wrapping PSR-3 loggers](#wrapping-psr-3-loggers)

---



## MU plugin example

For example, let's assume that our hosting provider forces us to use a PSR-3 implementation that is not Monolog, but still implements PSR-3, for
example ["graylog2/gelf-php"](https://github.com/bzikarsky/gelf-php).

Let's imagine that our hosting ships a MU plugin like the following:

```php
/* Plugin Name: Awesome Hosting logger */

function awesome_hosting_logger(): Psr\Log\Loggerinterface
{
    static $logger;
    if (!$logger) {
        $transport = new Gelf\Transport\UdpTransport(
            '127.0.0.1',
            12201,
            Gelf\Transport\UdpTransport::CHUNK_SIZE_LAN
        );
        
        $publisher = new Gelf\Publisher();
        $publisher->addTransport($transport);
        $logger = new Gelf\Logger($publisher, home_url());
    }

    return $logger;
}
```

All of our plugins/themes/packages that are designed to accept a PSR-3 implementation could be injected with the above implementation instead of Wonolog implementation, and everything will work for them, but the PSR-3 logger will not be integrated in Wonolog , and thus:

- It will not be used to log WordPress core events handled via the Wonolog default hook listeners
- It will not be used to log any Wonolog custom hook listener
- It will not be used for plugins/packages/themes that use action hooks to perform logs (aka the "WordPressy" way, see the [*"Designing packages for Wonolog"*](./04-designing-packages-for-wonolog.md) chapter for details)
- It will not benefit of Wonolog WordPress context processor or any other processor added to Wonolog
- It will not take into account Wonolog-specific configuration, such us disable logging via `WONOLOG_DISABLE` environment variable or constant.

To leverage Wonolog and still use an "external" PSR-3 implementation, the suggested way to go is to "wrap" the PSR-3 logger in a Monolog handler, something that the `Monolog\Handler\PsrHandler` class makes very easy.



## Wrapping PSR-3 loggers

Assuming the MU plugin above is in place, we can still use Wonolog adding the logger as a Wonolog handler. Something like this:

```php
add_action(
    'wonolog.setup',
    static function (Inpsyde\Wonolog\Configurator $config) {
        $config->pushHandler(
            new Monolog\Handler\PsrHandler(
                awesome_hosting_logger(),
                Inpsyde\Wonolog\LogLevel::defaultMinLevel()
            )
        );
    }
);
```

That's it. The custom PSR-3 logger is now fully integrated into Wonolog.



---

1. [Introduction](./00-introduction.md)
2. [Anatomy of a Wonolog log record](./01-anatomy-of-a-wonolog-log-record.md)
3. [Bootstrap and configuration gateway](./02-bootstrap-and-configuration-gateway.md)
4. [What is logged by default](./03-what-is-logged-by-default.md)
5. [Designing packages for Wonolog](./04-designing-packages-for-wonolog.md)
6. [Logging code not designed for Wonolog](./05-logging-code-not-designed-for-wonolog.md)
7. [Log records handlers](./06-log-records-handlers.md)
8. [Log records processors](./07-log-records-processors.md)
9. **Custom PSR-3 loggers**
10. [Configuration cheat sheet](./09-configuration-cheat-sheet.md)

---

« [Log records processors](./07-log-records-processors.md) || [Configuration cheat sheet](./09-configuration-cheat-sheet.md) »
