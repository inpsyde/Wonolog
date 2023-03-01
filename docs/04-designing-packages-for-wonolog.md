# Designing packages for Wonolog

Wonolog advocates a pattern in which plugins/themes/packages do not write log records (because that requires knowledge of the infrastructure that is only available at the website level) but they "emit log events" that will be "listened" by Wonolog to persist them.

That pattern can be put in place in packages without depending on Wonolog. There are two ways that Wonolog supports "natively" to do it:

- the "WordPressy way", AKA using log-specific WordPress action hooks
- leverage the PSR-3 standard

---

# Table of contents

- [The "WordPressy" way](#the-wordPressy-way)
    - [Integrate a package using "WordPressy" way](#integrate-a-package-using-wordPressy-way)
    - [Log level for logging hooks](#log-level-for-logging-hooks)
- [The PSR way](#the-psr-way)
- [About log channel](#about-log-channel)
    - [Configuring default channel when using hooks](#configuring-default-channel-when-using-hooks)
    - [Configuring default channel when using PSR logger](#configuring-default-channel-when-using-PSR-logger)
    - [Global default channel](#global-default-channel)

---

## The "WordPressy" way

Let's assume there's a plugin that sends API requests using the WordPress HTTP API. And let's assume we want that plugin to log HTTP requests and responses. A bare minimum code could be like this:

```php
/* Plugin name: Prefix API calls. */

function prefix_call_api(string $endpoint, array $body = [], string $method = 'GET'): ?array
{
    try {
        $url = 'https://example.com/api/' . $endpoint;
        $params = compact('url', 'body', 'method');
    
        # Log message string + context array
        do_action('prefix_log.debug', 'Calling API...', $params);

        $response = wp_remote_request($url, compact('method', 'body'));
        if (is_wp_error($response)) {
            # Log WP_Error instance
            do_action('prefix_log.error', $response);

            return null;
        }

        $body = wp_remote_retrieve_body($response);        
        $json = json_decode($body, true, 512);
        if (!$json || !is_array($json)) {
            $params['body'] = $body;
            # Log message string + context array
            do_action('prefix_log.error', 'Invalid response', $params);

            return null;
        }

         # Log array with "message" key + arbitrary context data
        do_action(
            'prefix_log.info',
            ['message' => 'Valid response.', 'request' => $params, 'response' => $json]
        );

        return $json;
    } catch (\Throwable $throwable) {
         # Log Throwable instance
         do_action('prefix_log.error', $throwable);
        
        return null;
    }
}
```

The function above uses a series of action hooks, whose name always starts with the same prefix, plus a dot, and then one of the [PSR-3 log levels](https://www.php-fig.org/psr/psr-3/#5-psrlogloglevel).

The parameters passed by the action hooks are either:

- two parameters, one being a message string and the second an array of "context" data for the log
- one parameter being a `WP_Error`
- one parameter being a `Throwable`
- one parameter being an array with a `message` key plus other arbitrary "context" data.

These are all "sources" accepted by Wonolog, and thanks to that, the plugin can be integrated with Wonolog with a single line of configuration.

In other words, even if it is not dependent on Wonolog, the plugin is *natively compatible* with Wonolog.

### Integrate a package using "WordPressy" way

As pretty much any other Wonolog configuration, we need to use `wonolog.setup` to configure a plugin like the one above that uses hooks to perform logs. For example:

```php
add_action(
    'wonolog.setup',
    function (Inpsyde\Wonolog\Configurator $config) {
        $config->registerLogHook('prefix_log');
    }
);
```

That’s it. Calling `Configurator::registerLogHook()` passing to it the hook name used for logs, is enough to handle with Wonolog all log hooks performed by the plugin.

### Log level for logging hooks

It must be noted that appending the log level to the hook name is **not** a requirement.

Always using the same hook (e.g., `"prefix_log"` in the plugin example above) would be perfectly fine as well. In that case, the level would be calculated by Wonolog: it would be "error" when the passed parameter would be a `WP_Error`, would be "critical" when the given parameter would be a `Throwable`, and would be "debug" in other cases.

Moreover, as an alternative to use log-level-specific hook names, it is possible to pass the log level as part of the log "context". For example, the two lines below are equivalent:

```php
do_action('prefix_log.error', 'Erroneous response', ['code' => 404]);
do_action('prefix_log', 'Erroneous response', ['level' => 'error', 'code' => 404]);
```

In the case the level is passed as context when *also* using a log-level-specific hook name, the level Wonolog will take into account is the one with higher severity.

## The PSR way

Sometimes WordPress plugins/packages have a dependency on PSR-3, either by developers' choice or because the plugin depends on some PHP library that, in its turn, depends on PSR-3.

As an example of that, let’s write a plugin that does the same thing as the previous plugin example but uses [Guzzle library](https://docs.guzzlephp.org/en/stable/):

```php
/* Plugin name: Prefix API calls. */

use GuzzleHttp\{Client, HandlerStack, Middleware, MessageFormatter};
use Psr\Log;

function prefix_call_api(string $endpoint, array $body = [], string $method = 'GET'): ?array
{
    # Which PSR-3 implementation to use?
    $logger = apply_filters('prefix_logger', null);
    if (!$logger instanceof Log\LoggerInterface) {
        $logger = new Log\NullLogger();
    }

    try {
        $formatter = new MessageFormatter('{request} - {response}');
        $stack = HandlerStack::create();
        $stack->push(Middleware::log($logger, $formatter));
        $client = new Client([
            'base_uri' => 'https://example.com/api/',
            'handler' => $stack,
        ]);

        $body = $client->request($method, $endpoint)->getBody();
        $json = json_decode($body, true);
        if (!$json || !is_array($json)) {
            $logger->error('Invalid response', compact('endpoint', 'body', 'method'));

            return null;
        }

        $logger->error('Valid response', compact('endpoint', 'method', 'json'));

        return $json;
    } catch (\Throwable $exception) {
         $logger->error($exception->getMessage(), compact('exception'));

        return null;
    }
}
```

The function above uses the same logic as the previous plugin example but uses Guzzle instead of WP HTTP API.

Because Guzzle has native support for PSR-3, it makes sense for the plugin to leverage PSR-3 for log records not directly triggered by Guzzle, e.g., to log the `Throwable` object.

Because the plugin can work with *any* PSR-3 implementation, it uses a filter hook, `"prefix_logger"` to allow consumers to decide which logger implementation to use. Thanks to that filter, we can " inject" the Wonolog PSR-3 implementation, returned by the `Inpsyde\Wonolog\makeLogger()` function:

```php
add_filter('prefix_logger', 'Inpsyde\Wonolog\makeLogger');
```

That’s it. The single line above is enough to ensure Wonolog is used for logging everything the plugin does and anything Guzzle will do for the plugin.

Using a filter to accept a PSR-3 `LoggerInterface` implementation is just one of the possible strategies plugins can use, but as long as it is possible to "inject" a PSR-3 `LoggerInterface`, the `makeLogger()` function will be enough to integrate such plugins with Wonolog.

## About log channel

Wonolog has the concept of "channel", which is a way to "categorize" logs. Each log record always has one and one only channel.

The channel is used to determine the way log records are handled. For example, log records about "security" might be treated in a way, and log records about "database" might be treated in some other way.

Neither of the two ways of integrating with Monolog ("*WordPressy*" way and PSR-3 way) shows a way to assign a channel to a log record, and that's because "channel" is a concept specific to Wonolog.

When a log record has no channel, Wonolog tries to "automagically" determine one.

- When the log record "context" array has a key `channel`, Wonolog uses that context value as the log record channel.
- When a log record created using an action hook passes a `Throwable` as the hook argument or when a PSR-3 log context as an "exception" key that holds a `Throwable` instance, Wonolog uses the "*PHP-ERROR*" channel.
- When a log record is created using an action hook passing a `WP_Error` instance, Wonolog tries to determine the channel based on the error message, e.g., if it contains the word "*database*", Wonolog uses the "*DB*" channel.

When none of the above applies, Wonolog uses a default channel.

### Configuring default channel when using hooks

When integrating plugins that make use of logging action hooks, it is possible to set a default channel when registering the hook alias. For example:

```php
add_action(
    'wonolog.setup',
    function (Inpsyde\Wonolog\Configurator $config) {
        $config->registerLogHook('prefix_log', Inpsyde\Wonolog\Channels::HTTP);
    }
);
```

In the snippet above, the default "*HTTP*" channel is used as the default channel for all the log records triggered via `"prefix_log"` action hook. Passing a channel explicitly in hook context would still override that.

Please note that instead of a default channel would have been possible to use a custom channel, like "*my-plugin*", or anything else.

### Configuring default channel when using PSR logger

When integrating plugins that make use of the Wonolog PSR-3 logger, it is possible to set a default channel when getting the logger instance. For example:

```php
add_filter('prefix_logger', static function(): Psr\Log\LoggerInterface {
    return Inpsyde\Wonolog\makeLogger('MY-PLUGIN');
});
```

In the snippet above, `"MY-PLUGIN"` is a custom channel used as the default channel for all the logs processed by the PSR-3 logger.

### Global default channel

When it is not possible to determine the channel in any of the ways listed above, Wonolog uses the "global" default channel, which is `"DEBUG"` by default but can be configured via the `wonolog.setup` hook:

```php
add_action(
    'wonolog.setup',
    function (Inpsyde\Wonolog\Configurator $config) {
        $config->withDefaultChannel('MY_APP');
    }
);
```

---

1. [Introduction](./00-introduction.md)
2. [Anatomy of a Wonolog log record](./01-anatomy-of-a-wonolog-log-record.md)
3. [Bootstrap and configuration gateway](./02-bootstrap-and-configuration-gateway.md)
4. [What is logged by default](./03-what-is-logged-by-default.md)
5. **Designing packages for Wonolog**
6. [Logging code not designed for Wonolog](./05-logging-code-not-designed-for-wonolog.md)
7. [Log records handlers](./06-log-records-handlers.md)
8. [Log records processors](./07-log-records-processors.md)
9. [Custom PSR-3 loggers](./08-custom-psr-3-loggers.md)
10. [Configuration cheat sheet](./09-configuration-cheat-sheet.md)

---

« [What is logged by default](./03-what-is-logged-by-default.md) || [Logging code not designed for Wonolog](./05-logging-code-not-designed-for-wonolog.md) »
