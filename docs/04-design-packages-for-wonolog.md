# Design packages for Wonolog

- Wonolog advocates a pattern in which plugins/themes/packages do not write log entries (because that requires knowledge of the infrastructure that is only available at the website level). Instead, they “emit log events” that will be “listened” by Wonolog to persist them.

    That pattern can be put in place in packages without depending on Wonolog. There are two ways that Wonolog supports “natively” to do it:

    - the “WordPressy way”, AKA using log-specific WordPress action hooks
    - leverage the PSR-3 standard



## The "WordPressy" way

Let's assume we write a plugin that sends API requests using the WordPress HTTP API. And let's assume we want the plugin log requests and responses. The bare minimum code could be like this:

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
        $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
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

The function above uses a series of action hooks, whose name always starts with the same prefix, and then a dot is used to add one of the [PSR-3 log levels](https://www.php-fig.org/psr/psr-3/#5-psrlogloglevel).

The parameters passed by the action hooks are either:

- Two parameters, one being a message string and the second an array of "context" data for the log
- One parameter being a `WP_Error`
- One parameter being a `Throwable`
- One parameter being an array with a `message` key plus other arbitrary "context" data.

These are all “sources” accepted by Wonolog, and thanks to that, the plugin can be integrated with Wonolog with a single line of configuration. In other words, even if it is not dependant on Wonolog, the plugin is *natively compatible* with Wonolog.



### Integrate a package using "WordPressy" way

As pretty much any Wonolog configuration, we need to use `wonolog.setup` to configure a plugin like the one above that uses hooks to perform logs. For example, to configure the plugin above:

```php
add_action(
    'wonolog.setup',
    funtion (Inpsyde\Wonolog\Configurator $config) {
        $config->registerLogHookAlias('prefix_log');
    }
);
```

That’s it. Calling `Configurator::registerLogHookAlias()` passing to it the hook name used for logs, is anything needed to handle all log hooks performed by the plugin.



### Log level for logging hooks

It must be noted that appending the log level to the hook name is **not** a requirement.

Always using the same hook (e.g., `"prefix_log"` in the plugin example above) would be perfectly fine as well. In that case, the level would be auto-determined by Wonolog: it would be "error" when the passed parameter would be a `WP_Error`, would be "critical" when the given parameter would be a `Throwable`, and would be "debug" in other cases.

Moreover, as an alternative to use log-level-specific hook names, it is possible to pass the log level as part of the log "context". For example, the two lines below are equivalent:

```php
do_action('prefix_log.error', 'Erroneous response', ['code' => 404]);
do_action('prefix_log', 'Erroneous response', ['level' => 'error', 'code' => 404]);
```

In the case the level is passed as context when also using a log-level-specific hook name, the level Wonolog will take into account is the one with higher severity.



## The PSR way

Sometimes WordPress plugins/packages have a dependency on PSR-3, either by developers’ choice or because the plugin depends on some PHP library that, in its turn, depends on PSR-3.

As an example of that, let’s write a plugin that does the same thing as the previous plugin example but uses [Guzzle library](https://docs.guzzlephp.org/en/stable/):

```php
/* Plugin name: Prefix API calls. */

use GuzzleHttp\{Client, HandlerStack, Middleware, MessageFormatter};
use Psr\Log;

function prefix_call_api(string $endpoint, array $body = [], string $method = 'GET'): ?array
{
    $logger = apply_filters('prefix_logger', null);
    if (!$logger instanceof Log\LoggerInterface) {
        $logger = new Log\NullLogger();
    }

    try {
        $formatter = new MessageFormatter('{request} - {response}');
        $stack = HandlerStack::create();
        $stack->push(Middleware::log($logger, $formatter);
        $client = new Client([
            'base_uri' => 'https://example.com/api/',
            'handler' => $stack,
        ]);

        $body = $client->request($method, $endpoint)->getBody();   
        $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        if (!$json || !is_array($json)) {
            $logger->error('Invalid response', compact('endpoint', 'body', 'method'));

            return null;
        }
                     
        $logger->error('Valid response', compact('endpoint', 'method', 'json'));

        return $json;
    } catch (\Throwable $exception) {
         $logger->error($throwable->getMessage(), compact('exception'));
        
        return null;
    }
}
```

The plugin uses the same logic as the previous plugin example but uses Guzzle instead of WP HTTP API. Because Guzzle has native support for PSR-3, it makes sense for the plugin to leverage PSR-3 for logs not directly triggered by Guzzle, e.g., to log the `Throwable`.

Because the plugin can work with any PSR-3 implementation, it uses a filter hook, `"prefix_logger"` to allow consumers to decide which logger to use. Thanks to that filter, we can " inject" the Wonolog PSR-3 implementation, which returned by the `Inpsyde\Wonolog\makeLogger()` function:

```php
add_filter('prefix_logger', 'Inpsyde\Wonolog\makeLogger');
```

That’s it. The above single line is anything needed to ensure Wonolog is used for logging everything the plugin does and anything Guzzle will do for the plugin.

Using a filter to accept a PSR-3 `LoggerInterface` implementation is just one of the possible strategies plugins can use, but as long as it is possible to "inject" a PSR-3 `LoggerInterface`, the `makeLogger()` function will be all is needed to integrate such plugins with Wonolog.



## About log channel

Wonolog has the concept of "channel", which is a way to "categorize" logs. Each log always has one and one only channel.

The log channel is used to determine the way logs are handled. For example, logs about "security" might be treated in a way, and logs about "database" might be treated in some other way.

The implementation behind this is that **each "channel" is assigned to one or more Monolog handlers**, and so the channel set to a log entry will determine what is done with it.

Neither of the two ways of integrating with Monolog ("*WordPressy*" way and PSR-3 way) shows a way to assign a channel to a log entry, and that's because "channel" is something specific to Wonolog.

When a log has no channel, Wonolog tries to "automagically" determine the channel.

- When the log "context" array has a key "channel", Wonolog uses that as the log channel.
- When the log created using an action hook passes a `Throwable` as the hook argument or when a PSR-3 log context as an "exception" key that holds a `Throwable` instance, Wonolog uses the "PHP-ERROR" channel.
- When the log is created using an action hook passing a `WP_Error` instance, Wonolog tries to determine the channel based on the error message, e.g., if it contains the word "database", Wonolog uses the "DB" channel.

When none of the above applies, Wonolog uses a default channel.

### Default channel when using hooks

When using action hooks to perform logs, it is possible to set a default channel when registering the hook alias. For example:

```php
add_action(
    'wonolog.setup',
    funtion (Inpsyde\Wonolog\Configurator $config) {
        $config->registerLogHookAlias('prefix_log', Inpsyde\Wonolog\Channels::HTTP);
    }
);
```

In the snippet above, the default "HTTP" channel is assigned as the default channel for all the logs triggered via "prefix_log" action hook. Passing a channel explicitly in hook context would still override that.

Please note that instead of a default channel would have been possible to use a custom channel, like "my-plugin", or anything else.



### Default channel when using PSR logger

When using the Wonolog PSR-3 logger obtained via `makeLogger()` function, it is possible to set a default channel when getting the logger. For example:

```php
add_filter('prefix_logger', function static (): Psr\Log\LoggerInterface {
    return Inpsyde\Wonolog\makeLogger("MY-PLUGIN");
});
```

In the snippet above, “MY-PLUGIN” is a custom channel used as the default channel for all the logs processed by the PSR-3 logger.



### Global default channel

When it is not possible to determine the channel in any of the ways listed above, Wonolog uses the “global” default channel, which is “DEBUG” by default but can be configured (as anything else in Wonolog) via the `wonolog.setup` hook:

```php
add_action(
    'wonolog.setup',
    funtion (Inpsyde\Wonolog\Configurator $config) {
        $config->withDefaultChannel("MY_APP");
    }
);
```



