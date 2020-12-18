# Introduction

If you're reading this you're probably interested in logging "things that happen in your WordPress plugin/theme/website.

Logging means _to persist somewhere_ information. When developing a plugin/theme/library we know nothing about the infrastructure where our code is executed, because thats is something that is only known at _website_ level. This is why **Wonolog is a package to be used at website level**.

**Wonolog does two things:**

- logs "things" that happen in WordPress (PHP errors and exceptions, 404 errors, cron jobs, HTTP API calls, etc...)
- provides an API to log "things" that happen in plugins/themes/packages



## Logging WordPress

As soon as Wonolog is installed, it starts working, even without any configuration at all. If `WP_DEBUG_LOG` constant is `true` (or it points to a log file) it will log *more things*, if  `WP_DEBUG_LOG` is false, Wonolog will only log *events* above the "warning" level (referring to [PSR-3 logging levels](https://www.php-fig.org/psr/psr-3/#5-psrlogloglevel)).

The documentation will contain abundant details on *what* exactly is logged, as well as _where_ and _how_ those logs are saved. For now, it suffices to say that by default there's a daily log file saved in a `/wonolog` sub-folder inside WordPress "uploads" folder.

*Note: Wonolog attemps to write an `.htaccess` file in that folder, to prevent public access, but that is not guaranteed to work, depending the webserver in use and its configuration. So customizing, at least, the folder where Wonolog saves logs to ensure is a folder not publicly-accessingle is highly recommended.*

Wonolog is extremely configurable. To configure granularly *what* is being logged is very easy, and thanks to [Monolog](https://seldaek.github.io/monolog/) under its hood, there are countless possibilities about *how* and *where* to save logs.



## Logging other code

When it comes to log code that is not WordPress core, there're two possibilities:

- code that we design to be compatible with Wonolog
- code we can't or don't want to make compatible with Wonolog

### Logging code "natively compatible" with Wonolog

"*Natively compatible with* Wonolog" does not mean *dependant on* Wonolog. It is very possible, recommended actually, to write plugin/themes/packages that do **not** depend on it.

Wonolog advocates a pattern in which plugins/themes/packages/etc does not directly write logs, but "emit" *events* that represent logs, leaving the burden of "listening to" those *events* and writing logs to code that is aware of the infrastructure. Wonolog is that code.

#### The "WordPressy way"

When talking of WordPress code, to "emit events" is something that can be accomplished without custom code nor 3rd party depenendecies by **firing an action hook**. That's all is needed to make any WordPress code "natively compatible" with Wonolog: having action hooks specifically designed to emit log "events". For example, a plugin having a code like this:

```php
do_action("my_plugin_log", $thing_to_log);
```

will be *natively compatible* with Wonolog. But what about `$thing_to_log`? Wonolog supports out of the box arbitrary strings, throwable objects, `WP_Error` object as well as array having a `message` key pointing to a log message. Even if more details will be provided where due, it is worth mentioning here that Wonolog supports out-of-the-box action hoks in the format `"{$hook_name}.{$log_level}"` where log level is one of the PSR-3 log levels, e. g. action hooks like `"my_plugin_log.error"`, `"my_plugin_log.critical"` etc, are very well supported.

#### The "PSR way"

Wonolog uses [Monolog](https://seldaek.github.io/monolog/) under the hood, and Monolog is the most popular implementation of [PSR-3 standard](https://www.php-fig.org/psr/psr-3/). Which meand that Wonolog is a package that provides a PSR-3 implementation. You can leverage that in your plugin/themes/packages/etc.

When writing some code that, for any reason, decides to have a dependency on PSR-3, one thing that is worth doing (and makes the code *natively compatible* with Wonolog) is to implement the [PSR-3 `LoggerAware` interface](https://www.php-fig.org/psr/psr-3/#4-psrlogloggerawareinterface) for package objects that needs to perform logs, implementing a way for external code to call the **`setLogger` method**. Doing so, it will be extremely easy at website level to "inject" Wonolog PSR-3 logger implementation, and so make use of it to log plugin/theme/package events with Wonolog without being dependant (or aware) of Wonolog



#### Which one is better?

From the point of view of Wonolog integration, both the "WordPressy" and the "PSR" ways are equivalenty: in both cases **a single line of code** will suffice to integrate in Wonolog code that makes use of them.

The "PSR way" makes a lot of sense when the plugin/package has other PHP dependencies which rely on PSR-3.

Using hooks (aka the "WordPress way") can be a simple still powerful way to implement logs without any 3rd party dependency and with very little code.



## Logging code not "natively compatible" with Wonolog

It might be desirable to log "things" that are done by 3rd party plugins we don't control, or by plugins that even if would be possible to change, we don't want to.

Because we can't or we don't want to change the code, assuming that code is not "natively compatible" with Wonolog, then the "one line" integration is not possible. But there's hope.

Because, in any case, we are talking about *WordPress* code, it **will** fire hooks. Just they will not be "logging hooks". Which means what we need is a "compatibility layer" that listen to those "generic" WordPress hooks and convert them to "loggable objects".

That "compatibility layer" in Wonolog is represented by "hook listeners": objects that, as the name suggest, listen to hooks and return log objects.

Let's assume there's a premium 3rd party plugin for e-commerce. This plugin at some point add products in a cart, doing something like:

```php
save_product_in_cart($product);
do_action('prefix_product_added_to_cart', $product);
```

We could write a compatility layer for it. That should be a MU plugin or a package to be installed at website level, so it could be dependant from Wonolog and make use of is `ActionListener` interface, writing something more or less like this:

```php
use Inpsyde\Wonolog;

class PluginNameWonologCompat implements Wonolog\HookListener\ActionListener
{
    public function listenTo(): array
    {
        return ['prefix_product_added_to_cart'];
    }
    
    public function update(string $hook, array $args, Wonolog\LogActionUpdater $updater): void
    {
        $updater->update(new Wonolog\Data\Info($hook, "plugin-name" $args[0]->ID);
    }
}
```

Note that this is exactly the approach Wonolog uses to log WordPress "events", being of course WordPress not natively compatible with Wonolog.

In fact, Wonolog comes with a few defaults `ActionListener` that "listen to" core WordPress hooks.

This approach surely require _some_ work, but when there's no possibility to change the source code that perform tasks we want to log, this approach provides a well working structure that it is easy to use, and to re-use.