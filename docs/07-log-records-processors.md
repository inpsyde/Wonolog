# Log records processors

A "processor" is a callback that receives a log record in form of array, and returns a possibly modified log record, that should respect the same structure.

The log record array structure is described in [Monolog documentation](https://seldaek.github.io/monolog/doc/message-structure.html), as Wonolog inherits that from Monolog.

---

# Table of contents

- [Wonolog processor types](#wonolog-processor-types)
    - [Generic processors](#generic-processors)
    - [Channel-specific processors](#channel-specific-processors)
    - [Handlers processors](#handlers-processors)
- [WordPress context processor](#wordpress-context-processor)
    - [Disabling WordPress context processor](#disabling-wordpress-context-processor)

---

## Wonolog processor types

Similarly to what is done for handlers (and documented in the [*"Log records handlers"*](./06-log-records-handlers.md) chapter), in Wonolog processors are organized in two groups:

- Channel-specific processors: used to process records having specific channels
- Generic processors, that are used to process all records, regardless channel

### Generic processors

To add/remove generic processors, it is necessary to hook `wonolog.setup` and call, respectively, `Configurator::pushProcessor()` / `Configurator::removeProcessor()` methods, for example:

```php
add_action(
    'wonolog.setup',
    function (Inpsyde\Wonolog\Configurator $config) {
        $config
            ->pushProcessor('my-processor', function (array $record): array {
                return $record;
            })
            ->removeProcessor('my-processor');
    }
);
```

The first parameter of `Configurator::pushProcessor()` is the processor identifier, which has to match the parameter passed to `Configurator::removeProcessor()` to remove the added processor.

Wonolog handlers and hook listeners also use identifiers, but when adding handlers and hook listeners passing an identifier is optional, because being objects it is possible for Wonolog to default to class names. Considering processors are arbitrary callbacks passing the identifier is required.

### Channel-specific processors

To add/remove channel-specific processors, it is necessary to hook `wonolog.setup` and call, respectively `Configurator::pushProcessorForChannels()` / `Configurator::removeProcessorFromChannels()`, for example:

```php
add_action(
    'wonolog.setup',
    function (Inpsyde\Wonolog\Configurator $config) {
        $config->pushProcessorForChannels(
            'my-processor',
            function (array $record): array {
                return $record;
            },
            'channel-1',
            'channel-2'
        );
    }
);
```

Note that a processor added via `pushProcessorForChannels`, can also be removed using the "generic" `removeProcessor`, in that case the processors will be removed from all the channels it was added to.

In the same way, it is possible to use `removeProcessorFromChannels` to disable only for specific channels a "generic" processor previously added via `pushProcessor()` .

On top of the methods described above, `Configurator` has two additional methods: `enableProcessorForChannels` and `enableProcessorsForChannel` which have the same scope: add existing processor(s) to existing channel(s).

Let's imagine, for example, that one MU plugin/package adds the configuration in the snippet above, *another* MU plugin/package could do:

```php
add_action(
    'wonolog.setup',
    function (Inpsyde\Wonolog\Configurator $config) {
        $config->enableProcessorForChannels(
            'my-processor',
            'channel-3',
            'channel-4'
        );
    }
);
```

The code above assign the `'my-processor'` processor to two additional channels besides the two channels that it was already assigned to.

The method `enableProcessorsForChannel` works in a similar way, but has a different signature that takes a single channel as first parameter, and a variadic number of processor identifiers from second parameter.

### Handlers processors

It is worth mention here that Monolog supports handler-specific processors that are used to process records before are handled by the specific handler(s) they are attached to.

Wonolog does not offer any configuration entry-point for handler-specific processors, and the reason is that in case developers needs handlers-specific processors they can add processors directly to the handlers, before adding them to Wonolog.

In the edge-case a MU plugin adds a handler to Wonolog, and _another_ MU plugin needs to add one or more handler-specific processors to that handler, it is possible to use the action hook `'wonolog.processable-handler-setup'`, that is fired once per handler, assuming the handler
implements `ProcessableHandlerInterface`.

That hook also passes an instance of `ProcessorsRegistry`, which allows to add to the handler _existing_ processors.

For example:

```php
add_action(
    'wonolog.processable-handler-setup',
    function (
        Monolog\Handler\ProcessableHandlerInterface $handler,
        string $identifier,
        Inpsyde\Wonolog\Registry\ProcessorsRegistry $processors
    ) {
        if ($identifier !== SomeHandler::class) {
            return;
        }

        // a new processor
        $handler->pushProcessor(function (array $record) {
            return $record;
        });
        
        // a processor available in the registry
        $processor = $processors->findById('my-processor');
        $processor and $handler->pushProcessor($processor);
    },
    10,
    3
);
```

## WordPress' context processor

By default, Wonolog adds a processor to *all* channels. It is implemented as an invokable class: `Inpsyde\Wonolog\Processor\WpContextProcessor`.

This processor adds WordPress-specific information in the log record "context".

For example, using a code like the following:

```php
// Wonolog PSR-3 logger
$logger = Inpsyde\Wonolog\makeLogger();
$logger->info('Something happened', ['foo' => 'bar']);
```

The log record context passed to handlers will be something like this:

```php
[
    'foo' => 'bar',  // passed when executing log
    'extra' => [
        'wp' => [    // added by Wonolog processor
            'doing_cron' => false,
            'doing_ajax' => false,
            'doing_rest' => false,
            'is_admin' => false,
            'user_id' => 123,
            'ms_switched' => false, // only on multisite
            'site_id' => 5,         // only on multisite
            'network_id' => 1,      // only on multisite
        ],
    ],
];
```

### Disabling WordPress context processor

It might be desirable to remove the default WordPress context processor, for all or for specific channels, e.g. to reduce the size of log records, in the case that is relevant. For example:

```php
add_action(
    'wonolog.setup',
    function (Inpsyde\Wonolog\Configurator $config) {
        $config->disableWpContextProcessor();
        // or
        $config->disableWpContextProcessorForChannels('PLUGIN_1', 'PLUGIN_1');
    }
);
```

---

1. [Introduction](./00-introduction.md)
2. [Anatomy of a Wonolog log record](./01-anatomy-of-a-wonolog-log-record.md)
3. [Bootstrap and configuration gateway](./02-bootstrap-and-configuration-gateway.md)
4. [What is logged by default](./03-what-is-logged-by-default.md)
5. [Designing packages for Wonolog](./04-designing-packages-for-wonolog.md)
6. [Logging code not designed for Wonolog](./05-logging-code-not-designed-for-wonolog.md)
7. [Log records handlers](./06-log-records-handlers.md)
8. **Log records processors**
9. [Custom PSR-3 loggers](./08-custom-psr-3-loggers.md)
10. [Configuration cheat sheet](./09-configuration-cheat-sheet.md)

---

« [Log records handlers](./06-log-records-handlers.md) || [Custom PSR-3 loggers](./08-custom-psr-3-loggers.md) »
