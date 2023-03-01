# Configuration cheat sheet

All configuration in Wonolog is done calling methods on the `Inpsyde\Wonolog\Configurator` object, an instance of which is passed by the `wonolog.setup` hook.

```php
add_action(
    'wonolog.setup',
    static function (Inpsyde\Wonolog\Configurator $config) {
        // configuration here
    }
);
```

The object has a quite a lot of methods, this chapter is a guide across all of them.

---

# Table of contents

- [Channels configuration](#channels-configuration)
- [Handlers configuration](#handlers-configuration)
- [Fallback handler configuration](#fallback-handler-configuration)
- [Processors configuration](#processors-configuration)
- [WordPress context processor configuration](#wordpress-context-processor-configuration)
- [Default hook listeners configuration](#default-hook-listeners-configuration)
- [Custom hook listeners configuration](#custom-hook-listeners-configuration)
- [Log hooks configuration](#log-hooks-configuration)
- [PHP errors and exceptions logging configuration](#php-errors-and-exceptions-logging-configuration)
- [Other configurations](#other-configurations)

---

## Channels configuration

```php
public function withChannels(string $channel, string ...$channels): Inpsyde\Wonolog\Configurator
```

Adds a channel to Wonolog. To be handled by Wonolog a log record needs to be registered in Wonolog.
This method is rarely needed, because all methods that accept channel-specific configuration
automatically register channels.



```php
public function withoutChannels(string $channel, string ...$channels): Inpsyde\Wonolog\Configurator
```

Remove a channel from Wonolog. To be handled by Wonolog a log record needs to be registered in
Wonolog. Removing a channel effectively means preventing Wonolog to handle any log record that holds
that channel.



```php
public function withDefaultChannel(string $channel): Inpsyde\Wonolog\Configurator
```

Makes a channel the default one in Wonolog, meaning that any log record without an explicit channel will be assigned to it.



## Handlers configuration

```php
public function pushHandler(
    Monolog\Handler\HandlerInterface $handler,
    ?string $identifier = null
): Inpsyde\Wonolog\Configurator
```

Adds an handler to be used for all channels. See [*"Log records handlers"*](./06-log-records-handlers.md) chapter for details.



```php
public function pushHandlerForChannels(
    Monolog\Handler\HandlerInterface $handler,
    ?string $identifier,
    string $channel,
    string ...$channels
): Inpsyde\Wonolog\Configurator
```

Adds an handler to be used for specific channels. See  [*"Log records handlers"*](./06-log-records-handlers.md) chapter for details.



```php
public function enableHandlersForChannel(
    string $channel,
    string $handlerIdentifier,
    string ...$handlerIdentifiers
): Inpsyde\Wonolog\Configurator
```

Enable already added handlers to be used for a channel. See  [*"Log records handlers"*](./06-log-records-handlers.md) chapter for details.



```php
public function enableHandlerForChannels(
    string $handlerIdentifier,
    string $channel,
    string ...$channels
): Inpsyde\Wonolog\Configurator
```

Enable an already added handler to be used for specific channels. See  [*"Log records handlers"*](./06-log-records-handlers.md) chapter for details.



```php
public function removeHandler(string $identifier): Inpsyde\Wonolog\Configurator
```

Remove an handler from all channels. See  [*"Log records handlers"*](./06-log-records-handlers.md) chapter for details.



```php
public function removeHandlerFromChannels(
    string $identifier,
    string $channel,
    string ...$channels
): Inpsyde\Wonolog\Configurator
```

Remove an handler from specific channels. See  [*"Log records handlers"*](./06-log-records-handlers.md) chapter for details.



## Fallback handler configuration

```php
public function enableFallbackHandler(): Inpsyde\Wonolog\Configurator
```

Enable fallback handler for all channels. This is the default behavior, hence calling this method makes sense only if the fallback handler was previously disabled for any/all channels. See  [*"Log records handlers"*](./06-log-records-handlers.md) chapter for details.



```php
 public function disableFallbackHandler(): Inpsyde\Wonolog\Configurator
```

Disable fallback handler for all channels. Log records having a channel not assigned to any channel will not be logged. See  [*"Log records handlers"*](./06-log-records-handlers.md) chapter for details.



```php
public function enableFallbackHandlerForChannels(
    string $channel,
    string ...$channels
): Inpsyde\Wonolog\Configurator
```

Enable fallback handler for specific channels. Opt-in mode: fallback handler will be used only for channels explicitly mentioned. See  [*"Log records handlers"*](./06-log-records-handlers.md) chapter for details.



```php
public function disableFallbackHandlerForChannels(
    string $channel,
    string ...$channels
): Inpsyde\Wonolog\Configurator
```

Disable fallback handler for specific channels. Opt-out mode: fallback handler will be used for all channels excluding those explicitly mentioned. See  [*"Log records handlers"*](./06-log-records-handlers.md) chapter for details.



## Processors configuration

```php
public function pushProcessor(
    string $identifier,
    callable $processor
): Inpsyde\Wonolog\Configurator
```

Adds a processor to be used for all channels. See [*"Log records processors"*](./07-log-records-processors.md) chapter for details.



```php
public function pushProcessorForChannels(
    string $identifier,
    callable $processor,
    string $channel,
    string ...$channels
): Inpsyde\Wonolog\Configurator
```

Adds a processor to be used for specific channels. See [*"Log records processors"*](./07-log-records-processors.md) chapter for details.



```php
public function enableProcessorsForChannel(
    string $channel,
    string $identifier,
    string ...$identifiers
): Inpsyde\Wonolog\Configurator
```

Enable already added processors to be used for a channel. See [*"Log records processors"*](./07-log-records-processors.md) chapter for details.



```php
public function enableProcessorForChannels(
    string $identifier,
    string $channel,
    string ...$channels
): Inpsyde\Wonolog\Configurator
```

Enable an already added processor to be used for specific channels. See [*"Log records processors"*](./07-log-records-processors.md) chapter for details.



```php
public function removeProcessor(string $identifier): Inpsyde\Wonolog\Configurator
```

Remove a processors from all channels. See [*"Log records processors"*](./07-log-records-processors.md) chapter for details.



```php
public function removeProcessorFromChannels(
    string $identifier,
    string $channel,
    string ...$channels
): Inpsyde\Wonolog\Configurator
```

Remove a processor from specific channels. See [*"Log records processors"*](./07-log-records-processors.md) chapter for details.



## WordPress context processor configuration

```php
public function enableWpContextProcessor(): Inpsyde\Wonolog\Configurator
```

Enable WP context processor for all channels. This is the default behavior, hence calling thismethod makes sense only if the WP context processor was previously disabled for any/all channels. See [*"Log records processors"*](./07-log-records-processors.md) chapter for details.



```php
public function disableWpContextProcessor(): Inpsyde\Wonolog\Configurator
```

Disable WP context processor for all channels. See [*"Log records processors"*](./07-log-records-processors.md) chapter for details.



```php
public function enableWpContextProcessorForChannels(
    string $channel,
    string ...$channels
): Inpsyde\Wonolog\Configurator
```

Enable WP context processor for specific channels. Opt-in mode: only channels explicitly mentioned will receive the WP context processor. See [*"Log records processors"*](./07-log-records-processors.md) chapter for details.



```php
public function disableWpContextProcessorForChannels(
    string $channel,
    string ...$channels
): Inpsyde\Wonolog\Configurator
```

Disable WP context processor for specific channels. Opt-out mode: only channels explicitly mentioned
will not receive the WP context processor. See [*"Log records processors"*](./07-log-records-processors.md) chapter for details.



## Default hook listeners configuration

```php
public function enableAllDefaultHookListeners(): Inpsyde\Wonolog\Configurator
```

Enable all default hook listeners. This is the default behavior, hence calling this method makes sense only if all/any default hook listeners was previously disabled. See [*"What is logged by default"*](./03-what-is-logged-by-default.md) chapter for details.



```php
public function disableAllDefaultHookListeners(): Inpsyde\Wonolog\Configurator
```

Disable all all default hook listeners. See [*"What is logged by default"*](./03-what-is-logged-by-default.md) chapter for details.



```php
public function enableDefaultHookListeners(
    string $listener,
    string ...$listeners
): Inpsyde\Wonolog\Configurator
```

Enable specific default hook listeners. Opt-in mode: only explicitly given default handlers will be enabled. See [*"What is logged by default"*](./03-what-is-logged-by-default.md) chapter for details.



```php
public function disableDefaultHookListeners(
    string $listener,
    string ...$listeners
): Inpsyde\Wonolog\Configurator
```

Disable specific default hook listeners. Opt-out mode: only explicitly given default handlers will not be enabled. See [*"What is logged by default"*](./03-what-is-logged-by-default.md) chapter for details.



## Custom hook listeners configuration

```php
public function addActionListener(
    Inpsyde\Wonolog\HookListener\ActionListener $listener,
    ?string $identifier = null
): Inpsyde\Wonolog\Configurator
```

Adds a custom action listener. See [*"Logging code not designed for Wonolog"*](./05-logging-code-not-designed-for-wonolog.md) chapter for details.



```php
public function addActionListenerWithPriority(
    Inpsyde\Wonolog\HookListener\ActionListener $listener,
    int $priority,
    ?string $identifier = null
): Inpsyde\Wonolog\Configurator
```

Adds a custom action listener with specific priority. See [*"Logging code not designed for Wonolog"*](./05-logging-code-not-designed-for-wonolog.md) chapter for details.



```php
public function addFilterListener(
    Inpsyde\Wonolog\HookListener\FilterListener $listener,
    ?string $identifier = null
): Inpsyde\Wonolog\Configurator
```

Adds a custom filter listener. See [*"Logging code not designed for Wonolog"*](./05-logging-code-not-designed-for-wonolog.md) chapter for details.



```php
public function addFilterListenerWithPriority(
    Inpsyde\Wonolog\HookListener\FilterListener $listener,
    int $priority,
    ?string $identifier = null
): Inpsyde\Wonolog\Configurator
```

Adds a custom filter listener with specific priority. See [*"Logging code not designed for Wonolog"*](./05-logging-code-not-designed-for-wonolog.md) chapter for details.



## Log hooks configuration

```php
public function registerLogHook(
    string $alias,
    ?string $defaultChannel = null
): Inpsyde\Wonolog\Configurator
```

Register an logging hook. See [*"Designing packages for Wonolog"*](./04-designing-packages-for-wonolog.md) chapter for details.



```php
public function withBaseHookPriority(int $priority): Inpsyde\Wonolog\Configurator
```

Set the default hook priority used to listen to hooks registered via `registerLogHook` method. Is
also used as default priority for hook listeners when no priority is explicitly set.



## PHP errors and exceptions logging configuration

```php
public function logPhpErrorsAndExceptions(): Inpsyde\Wonolog\Configurator
```

Tells Wonolog to log both PHP errors and exceptions. Default behavior.



```php
public function doNotLogPhpErrorsNorExceptions(): Inpsyde\Wonolog\Configurator
```

Tells Wonolog to do not log PHP errors nor exceptions.



```php
public function doNotLogPhpErrors(): Inpsyde\Wonolog\Configurator
```

Tells Wonolog to do not log PHP errors.



```php
public function doNotLogPhpExceptions(): Inpsyde\Wonolog\Configurator
```

Tells Wonolog to do not log PHP exceptions.



```php
public function logPhpErrorsTypes(int $errorTypes): Inpsyde\Wonolog\Configurator
```

Tells Wonolog to log only specific error types. Accept bitmask of [error types constants](https://www.php.net/manual/en/errorfunc.constants.php).



```php
public function logSilencedPhpErrors(): Inpsyde\Wonolog\Configurator
```



Tells Wonolog to log errors silenced via `@` operator.

```php
public function dontLogSilencedPhpErrors(): Inpsyde\Wonolog\Configurator
```

Tells Wonolog to do not log errors silenced via `@` operator. Default behavior.



## Other configurations

```php
public function useTimezone(DateTimeZone $zone): Inpsyde\Wonolog\Configurator
```

Set a timezone for time information in log entries. Wonolog defaults to WordPress timezone if not set.

```php
public function withIgnorePattern(
    string $ignorePattern,
    ?int $levelThreshold = null,
    string ...$channels
): Inpsyde\Wonolog\Configurator 
```

Tell Wonolog to ignore logs whose message matches the given regular expression pattern. Optionally it is possible to provide a level threshold to avoid ignore high severity logs. It is also possible to limit the effect to specific channels.



---

1. [Introduction](./00-introduction.md)
2. [Anatomy of a Wonolog log record](./01-anatomy-of-a-wonolog-log-record.md)
3. [Bootstrap and configuration gateway](./02-bootstrap-and-configuration-gateway.md)
4. [What is logged by default](./03-what-is-logged-by-default.md)
5. [Designing packages for Wonolog](./04-designing-packages-for-wonolog.md)
6. [Logging code not designed for Wonolog](./05-logging-code-not-designed-for-wonolog.md)
7. [Log records handlers](./06-log-records-handlers.md)
8. [Log records processors](./07-log-records-processors.md)
9. [Custom PSR-3 loggers](./08-custom-psr-3-loggers.md)
10. **Configuration cheat sheet**

---

« [Custom PSR-3 loggers](./08-custom-psr-3-loggers.md)
