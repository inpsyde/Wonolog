# Introduction

If you're reading this, you're probably interested in logging "things that happen in your WordPress plugin/theme/website.

Logging means *to persist somewhere* information. When developing a plugin/theme/library, we know nothing about the infrastructure where our code runs because it is only known at *the website* level. That is why **Wonolog is a package to be used at the website level**.

**Wonolog does two things:**

- logs "things" that happen in WordPress (PHP errors and exceptions, 404 errors, cron jobs, HTTP API calls, etc...)
- provides an API to log "things" that happen in plugins/themes/packages



## Logging WordPress

As soon as Wonolog is installed, it starts working, even without any configuration at all. If `WP_DEBUG_LOG` constant is true (or it points to a log file), it will log *more things.* If `WP_DEBUG_LOG` is false, Wonolog will only log *events* above the "warning" level (referring to [PSR-3 logging levels](https://www.php-fig.org/psr/psr-3/#5-psrlogloglevel)).

The documentation will contain many details on *what* exactly is logged and *where* and *how* those logs are saved. For now, it suffices to say that by default, there's a daily log file saved in a `/wonolog` sub-folder inside WordPress "uploads" folder.

*Note: Wonolog attempts to write a .htaccess file in that folder to prevent public access, but that is not guaranteed to work, depending on the webserver in use and its configuration. So customizing, at least, the folder where Wonolog saves logs to ensure it is not publicly-accessible is highly recommended.*

Wonolog is exceptionally configurable. It's straightforward, for example, to granularly configure *what* it logs, and thanks to [Monolog](https://seldaek.github.io/monolog/) under its hood, there are countless possibilities about *how* and *where* to save logs.



## Logging other code

When it comes to log code that is *not* WordPress core, there're two possibilities:

- code that we design to be compatible with Wonolog
- code we can't or don't want to make compatible with Wonolog

### Logging code "natively compatible" with Wonolog

"*Natively compatible with* Wonolog" does not mean *dependant on* Wonolog. In fact, it is possible, recommended actually, to write plugin/themes/packages that do **not** depend on it.

Wonolog advocates a pattern in which plugins/themes/packages do not directly *write* logs but "emit" *events* that represent logs, leaving the burden of "listening to" those *events* and writing logs to code that is aware of the infrastructure. Wonolog is that code.

#### The "WordPressy way"

When talking of WordPress code, to "emit events" can be accomplished without custom code or 3rd party dependencies by **firing an action hook**. That's all is needed to make any WordPress code "natively compatible" with Wonolog: having action hooks designed specifically to emit log "events". For example, a plugin that has a code like this:

```php
do_action("my_plugin_log", $thing_to_log);
```

will be *natively compatible* with Wonolog. But what about $thing_to_log? Wonolog supports out of the box arbitrary strings, throwable objects, WP_Error object, and array having a message key pointing to a log message. Even if more details will be provided where due, it is worth mentioning here that Wonolog supports out-of-the-box action hooks in the format "{$hook_name}.{$log_level}" where log level is one of the PSR-3 log levels, e. g. action hooks like "my_plugin_log.error", "my_plugin_log.critical" etc, are very well supported.

#### The "PSR way"

Wonolog uses [Monolog](https://seldaek.github.io/monolog/) under the hood, and Monolog is the most popular implementation of [the PSR-3 standard](https://www.php-fig.org/psr/psr-3/). This means that Wonolog is itself a package that provides a PSR-3 performance. You can leverage that in your plugin/themes/packages/etc.

When code has a dependency on PSR-3, one thing that is worth doing is implementing the [PSR-3 `LoggerAware` interface](https://www.php-fig.org/psr/psr-3/#4-psrlogloggerawareinterface) for package objects that need to perform logs, also implementing a way for external code to call the **`setLogger` method**. Doing so will be straightforward at the website level to "inject" Wonolog PSR-3 logger implementation and make use of it to log events in plugin/theme/package without them being dependant on (or aware of) Wonolog.

#### Which one is better?

From the perspective of Wonolog integration, both the "*WordPressy*" and the "*PSR*" ways are equivalent: in both cases, **a single line of code** will suffice to integrate with Wonolog code that makes use of them.

The "PSR way" makes a lot of sense when the plugin/package has other PHP dependencies that rely on PSR-3.

Using hooks (aka the "*WordPress way*") can be a simple still powerful way to implement logs without any 3rd party dependency and very little code.



### Logging code not "natively compatible" with Wonolog

It might be desirable to log "things" done by 3rd party plugins we don't control or plugins that we don't want to change.

Because we can't or don't want to change the code, assuming that code is not "natively compatible" with Wonolog, then the "one line" integration is impossible. But there's hope.

Because, in any case, we are talking about *WordPress* code, it **will** fire hooks, but they will not be "logging hooks". What we need is a "compatibility layer" that listens to those "generic" WordPress hooks and converts them to "loggable objects".

That "compatibility layer" in Wonolog is represented by "hook listeners": objects that, as the name suggests, listen to hooks use Wonolog to perform logs.

Let's assume there's a premium 3rd party plugin for e-commerce. This plugin at some point add products in a cart, doing something like:

```php
save_product_in_cart($product);
do_action('prefix_product_added_to_cart', $product);
```

We could write a compatibility layer for it. That should be a MU plugin or a package to be installed at the website level, so it could be dependant on Wonolog and make use of is ActionListener interface, writing something more or less like this:

```php
use Inpsyde\Wonolog; 

class PluginNameWonologCompat implements Wonolog\HookListener\ActionListener {
     
    public function listenTo(): array {
        return ['prefix_product_added_to_cart'];
    }

    public function update(string $hook, array $args, Wonolog\LogActionUpdater $updater): void {
        $updater->update(new Wonolog\Data\Info($hook, "plugin-name", $args[0]->ID));
    }
}
```

Please note that is precisely the approach Wonolog uses to log WordPress "events" as WordPress is not natively compatible with Wonolog.

Wonolog comes with a few defaults `ActionListener` that "listen to" core WordPress hooks.

This approach indeed requires *some* work, but when there's no possibility of changing the source code that performs tasks we want to log, this approach provides a well-working structure that is easy to use and re-use.
