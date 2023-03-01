# Logging code not designed for Wonolog

The [*"Designing packages for Wonolog"*](04-designing-packages-for-wonolog.md) provides detailed documentation on designing packages natively compatible with Wonolog, without requiring Wonolog as a dependency.

However, we can't expect all code to be natively compatible with Wonolog, especially when the code is 3rd party, so we can't make it compatible even if we would.

In those cases, the only left possibility to use Wonolog is to write a "compatibility later" through the writing of hook listeners.

---

# Table of contents

- [Hook listeners](#hook-listeners)
- [The interfaces](#the-interfaces)
    - [Utility traits](#utility-traits)
- [An example](#an-example)
- [Register hook listeners](#register-hook-listeners)
    - [Hook listeners priority](#hook-listeners-priority)
    - [Hook listeners identifier](#hook-listeners-identifier)

---

## Hook listeners

Hook listeners are objects implementing the `Inpsyde\Wonolog\HookListener\HookListener` interface, even if to be able to do anything useful, these objects have to implement either `Inpsyde\Wonolog\HookListener\ActionListener` or `Inpsyde\Wonolog\HookListener\FilterListener` (both extending the `HookListener` interface).

Hook listeners are what Wonolog uses to log WordPress core events. That should not come as a surprise: WordPress is not natively compatible with Wonolog, so we need hook listeners to introduce a compatibility layer between WordPress and Wonolog.

Hook listeners used for the core and shipped with Wonolog are referenced as "default hook listeners", but it is possible to write hook listeners as a compatibility layer with any WordPress plugin/theme/package.

## The interfaces

The three relevant interfaces are the following:

**1 - `HookListener`**

```php
namespace Inpsyde\Wonolog\HookListener;

interface HookListener
{
    public function listenTo(): array;
}
```

**2 - `ActionListener`**

```php
namespace Inpsyde\Wonolog\HookListener;

use Inpsyde\Wonolog\LogActionUpdater;

interface ActionListener extends HookListener
{
    public function update(string $hook, array $args, LogActionUpdater $updater): void;
}
```

**3 - `FilterListener`**

```php
namespace Inpsyde\Wonolog\HookListener;

use Inpsyde\Wonolog\LogActionUpdater;

interface FilterListener extends HookListener
{
    public function filter(string $hook, array $args, LogActionUpdater $updater);
}
```

The interfaces are quite simple. The idea behind them should also be straightforward to grasp for any developer familiar with WordPress actions and filters.

`ActionListener::update` is attached to action hooks, the hook being fired is passed as the first argument, the hook parameters as the second argument, and an instance of `LogActionUpdater` is given as the third parameter.

`FilterListener::filter` is attached to filter hooks, and accepts the same parameters, but being attached to filter hooks has to *return* something, usually the first among the hook parameters, considering that we typically don't want filter listeners to change the return value of the filter.

`FilterListener` should preferably **not** used at all, but the interface exists for those cases when the only chance to "intercept" a value we want to log is to use a filter hook.

### Utility traits

When forced to use `FilterListener`, a trait shipped with Wonolog, `Inpsyde\Wonolog\HookListener\FilterFromUpdateTrait` might come in handy. It allows writing a `FilterListener` as it would be an `ActionListener`. For example:

```php
use Inpsyde\Wonolog\{HookListener, Data, LogActionUpdater};

class MyFilterListener implements HookListener\FilterListener
{
    use HookListener\FilterFromUpdateTrait;
    
    public function listenTo(): array
    {
        return ['some_filter'];
    }
    
    public function update(string $hook, array $args, LogActionUpdater $updater): void
    {
        $updater->update(new Data\Debug("Filter {$hook} applied.", 'MY_PLUGIN', $args));
    }
}
```

For hook listeners that listen to multiple hooks, there's another trait, `Inpsyde\Wonolog\HookListener\MethodNamesByHookTrait`, that might also come in handy. It allows to don't write any `update` method at all, but instead write methods named after the listened hooks. For example:

```php
use Inpsyde\Wonolog\{HookListener, Data};

class MyActionListener implements HookListener\ActionListener
{
    use HookListener\MethodNamesByHookTrait;
    
    public function listenTo(): array
    {
        return ['hook_one', 'hook_two', 'hook_three'];
    }
    
    public function hookOne($arg1, $arg2): Data\Debug
    {
        return new Data\Debug('Action "hook_one" just fired.', 'MY_PLUGIN');
    }
    
    public function hookTwo($arg1, $arg2, $arg3, $arg4): Data\Debug
    {
        return new Data\Debug('Action "hook_two" just fired.', 'MY_PLUGIN');
    }
    
    public function hookThree($arg1): Data\Debug
    {
        return new Data\Debug('Action "hook_three" just fired.', 'MY_PLUGIN');
    }
}
```

The method `hookOne` is called for the `"hook_one"` hook, the method `hookTwo` is called for the `"hook_two"` hook, and so on.

The methods name in the snippet above are the "camelCase" version of the hook names, but the "snake_case" (e.g., `hook_one`) would have worked too, so it is possible to use method names that fit anyone code style.

It is worth noting that the method name is created by "splitting words" of the hook by any non-alphanumeric character and then merging the words either in "camelCase" or "snake_case". For example, for a hook `foo.bar.baz`, the called method would be `foo_bar_baz` or `fooBarBaz`, depending on what's defined.

Another interesting feature of this trait is the possibility to set a common prefix for hooks. For example:

```php
use Inpsyde\Wonolog\{HookListener, Data};

class PrefixActionListener implements HookListener\ActionListener
{
    use HookListener\MethodNamesByHookTrait;

    public function listenTo(): array
    {
        $this->withHookPrefix('prefix_');

        return ['prefix_something', 'prefix_foo'];
    }

    public function something($arg1, $arg2): Data\Debug
    {
        return new Data\Debug('Action "prefix_something" just fired.', 'MY_PLUGIN');
    }

    public function foo($arg1, $arg2, $arg3, $arg4): Data\Debug
    {
        return new Data\Debug('Action "prefix_foo" just fired.', 'MY_PLUGIN');
    }
}
```

By setting the hook prefix, method names don't need to be unnecessarily long to match exactly hook names.

Of course, the two utility traits might be used together if necessary.

## An example

Let's assume there's a 3rd party plugin that looks like the following:

```php
namespace Awesome\Premium\Plugin;

function perform_ajax_call() {
    $x = (int)sanitize_text_field($_POST['x'] ?? 0);
    $y = (int)sanitize_text_field($_POST['y'] ?? 0);

    wp_send_json(compact('x', 'y'));
}

add_action('wp_ajax_xy', 'Awesome\Premium\Plugin\perform_ajax_call');
add_action('wp_ajax_nopriv_xy', 'Awesome\Premium\Plugin\perform_ajax_call');
```

And let's assume we would like to log AJAX calls handled by the plugin. That is particularly tricky because no action is explicitly fired, and `wp_send_json` exits the request, so nothing is executed after that.

However, we know that [`wp_send_json`](https://developer.wordpress.org/reference/functions/wp_send_json/) internally calls [`wp_die`](https://developer.wordpress.org/reference/functions/wp_die/), and for AJAX calls, `wp_die` will fire [`wp_die_ajax_handler` filter](https://developer.wordpress.org/reference/hooks/wp_die_ajax_handler/), which allows us to write a filter listener like the following:

```php
use Inpsyde\Wonolog\{HookListener, Data};

class AwesomePremiumPluginListener implements HookListener\FilterListener
{
    use HookListener\MethodNamesByHookTrait;
    use HookListener\FilterFromUpdateTrait;

    public function listenTo(): array
    {
        return ['wp_die_ajax_handler'];
    }

    private function wpDieAjaxHandler(): ?Data\LogData
    {
        // this method is called for each "wp_die_ajax_handler" filter
        // we need to be sure we target only the right action.
        if (($_POST['action'] ?? null) !== 'xy') {
            return null;
        }

        $context = filter_input_array(
            INPUT_POST,
            ['x' => FILTER_SANITIZE_NUMBER_INT, 'y' => FILTER_SANITIZE_NUMBER_INT]
        );

        return new Data\Info('AJAX response sent', $context);
    }
}
```

The above listener works and shows both the utility traits in action, but it calculates the log record "context", duplicating the logic used by the plugin.

A possible alternative consists in leveraging the fact that filter listeners can change the filtered value, so we could wrap the AJAX handler callback:

```php
use Inpsyde\Wonolog\{HookListener, Data, LogActionUpdater};

class AwesomePremiumPluginListener implements HookListener\FilterListener
{
    public function listenTo(): array
    {
        return ['wp_die_ajax_handler'];
    }

    public function filter(string $hook, array $args, LogActionUpdater $updater)
    {
        $handler = $args ? reset($args) : null;

        if (($_POST['action'] ?? null) !== 'xy') {
            return $handler;
        }

        return static function (...$args) use ($handler, $updater)
        {
            $updater->update(new Data\Info('AJAX response sent', 'HTTP', $args));

            if (is_callable($handler)) {
                $handler(...$args);
            }
        };
    }
}
```

The example above has proved that hook listeners are quite powerful, flexible, and simple to write.
It also demonstrates that it's infrequent that there's no way to capture desired data to log: even for a case that at first look seemed very tricky, it was possible to find not one, but two alternatives to capture the data to log.

## Register hook listeners

To write a hook listener is not enough: we also need to make Wonolog aware of it. As usual, Wonolog configuration is done hooking `wonolog.setup`:

```php
add_action(
    'wonolog.setup',
    function (Inpsyde\Wonolog\Configurator $config) {
        $config->addFilterListener(new AwesomePremiumPluginListener());
    }
);
```

besides `addFilterListener` there's also `addActionListener`.

Even if it is preferable not to implement both `FilterListener` and `ActionListener` interfaces for the same listener, if that is the case, `addFilterListener` should be used to add listeners that implement both interfaces.

### Hook listeners priority

Sometimes, listening to a WordPress hook the _priority_ might be relevant. Usually Wonolog calculates it, but it might be passed explicitly using `Configurator::addActionListenerWithPriority` or `Configurator::addFilterListenerWithPriority` method:

```php
add_action(
    'wonolog.setup',
    function (Inpsyde\Wonolog\Configurator $config) {
        $config->addFilterListenerWithPriority(
            new AwesomePremiumPluginListener(),
            PHP_INT_MAX
        );
    }
);
```

### Hook listeners identifier

Internally, Wonolog keeps a "registry" of added hook listeners. The registry uses a map of unique identifiers to listeners.

By default, the fully-qualified class name of the added listener is used as an identifier. However, if multiple instances of the same class are purposely added because they have different behavior (thanks to different internal state), the default identifier-by-class strategy won't work. In that case it is necessary to pass the listener identifier explicitly, either as the second parameter of `addActionListener` / `addFilterListener` or as the third parameter of `Configurator::addActionListenerWithPriority` or `Configurator::addFilterListenerWithPriority`:

```php
add_action(
    'wonolog.setup',
    function (Inpsyde\Wonolog\Configurator $config) {
        $config
            ->addActionListener(new ConfigurableListener('foo'), 'foo-listener')
            ->addActionListener(new ConfigurableListener('bar'), 'bar-listener')
            ->addActionListenerWithPriority(new ConfigurableListener('baz'), 1, 'baz-listener');
    }
);
```

---

1. [Introduction](./00-introduction.md)
2. [Anatomy of a Wonolog log record](./01-anatomy-of-a-wonolog-log-record.md)
3. [Bootstrap and configuration gateway](./02-bootstrap-and-configuration-gateway.md)
4. [What is logged by default](./03-what-is-logged-by-default.md)
5. [Designing packages for Wonolog](./04-designing-packages-for-wonolog.md)
6. **Logging code not designed for Wonolog**
7. [Log records handlers](./06-log-records-handlers.md)
8. [Log records processors](./07-log-records-processors.md)
9. [Custom PSR-3 loggers](./08-custom-psr-3-loggers.md)
10. [Configuration cheat sheet](./09-configuration-cheat-sheet.md)

---

« [Designing packages for Wonolog](./04-designing-packages-for-wonolog.md) || [Log records handlers](./06-log-records-handlers.md) »
