# Introduction

If you're reading this, you're probably interested in logging "things that happen in your WordPress plugin/theme/website.

Logging means *to persist somewhere* information. When developing a plugin/theme/library, we know nothing about the infrastructure where our code runs because it is only known at *the website* level. That is why **Wonolog is a package to be used at the website level**.

**Wonolog does two things:**

- logs "things" that happen in WordPress (PHP errors and exceptions, 404 errors, cron jobs, HTTP API calls, etc...)
- integrates with plugins/themes/packages to log *their* "things"



## Logging WordPress

As soon as Wonolog is installed, it starts working, even without any configuration at all.

The chapter *"What is logged by default"* contains all the details on *what* exactly is logged, and the chapter "*Bootstrap and configuration gateway"* will describe and *where* and *how* those logs are saved by default. For now, it suffices to say that by default, there's a daily log file saved in a `/wonolog` sub-folder inside WordPress "uploads" folder.

The rest of documentation will show how configurable Wonolog is. It's straightforward, for example, to granularly configure *what* it logs, and thanks to [Monolog](https://seldaek.github.io/monolog/) under its hood, there are countless possibilities about *how* and *where* to save logs.



## Logging other code

When it comes to log code that is *not* WordPress core, there're two possibilities:

- code that we design to be compatible with Wonolog
- code we can't or don't want to make compatible with Wonolog



### Logging code "natively compatible" with Wonolog

"*Natively compatible with* Wonolog" does not mean *dependant on* Wonolog. In fact, it is possible, recommended actually, to write plugin/themes/packages that do **not** depend on it.

Wonolog advocates a pattern in which plugins/themes/packages do not directly *write* logs but "emit" *events* that represent logs, leaving the burden of "listening to" those *events* and writing logs to code that is aware of the infrastructure. Wonolog is that code.

The chapter "*Design packages for Wonolog*" provides all the details about writing packages natively compatible with Wonolog, but here's a introduction on the two possible approaches.

#### The "WordPressy way"

When talking of WordPress code, to "emit events" can be accomplished without custom code or 3rd party dependencies by **firing an action hook**. Having action hooks designed specifically to emit log "events" is enough to make any WordPress code "natively compatible" with Wonolog. For example, a plugin that has a code like the following will be *natively compatible* with Wonolog:

```php
do_action("my_plugin_log", $thing_to_log);
```

But what about `$thing_to_log`? Wonolog supports out of the box:

- arbitrary strings
- `Throwable` objects
- `WP_Error` objects
- arrays having a `"message"` key pointing to a log message.

#### The "PSR way"

Wonolog uses [Monolog](https://seldaek.github.io/monolog/) under the hood, and Monolog is the most popular implementation of [the PSR-3 standard](https://www.php-fig.org/psr/psr-3/). This means that Wonolog is itself a package that provides a PSR-3 implementation. You can leverage that in your plugin/themes/packages/etc.

When code has a dependency on PSR-3, one thing that is worth doing is implementing the [PSR-3 `LoggerAware` interface](https://www.php-fig.org/psr/psr-3/#4-psrlogloggerawareinterface) for package's objects that need to perform logs, also implementing a way for external code to "inject" a PSR-3 logger implementation. Code that does that can make use of Wonolog PSR-3 implementation, and so use Wonolog to log events without being dependant on (or aware of) it.

#### Which one is better?

From the perspective of Wonolog integration, both the "*WordPressy*" and the "*PSR*" ways are equivalent: in both cases, **a single line of code** will suffice to integrate with Wonolog code that makes use of them.

The "PSR way" makes a lot of sense when the plugin/package has other PHP dependencies that rely on PSR-3.

Using hooks (aka the "*WordPress way*") can be a simple still powerful way to implement logs without any 3rd party dependency and very little code.



### Logging code not "natively compatible" with Wonolog

It might be desirable to log "things" done by 3rd party plugins we don't control or plugins that we don't want to change.

Because we can't or don't want to change the code, assuming that code is not "natively compatible" with Wonolog, then the "one line" integration is impossible. But there's hope.

Because, in any case, we are talking about *WordPress* code, it **will** use hooks, but they will not be "logging hooks". What we need is a "compatibility layer" that listens to those "generic" WordPress hooks and map them to "loggable objects".

The "compatibility layer" in Wonolog is represented by "**hook listeners**": objects that, as the name suggests, listen to hooks use Wonolog to perform logs.

Please note that is precisely the approach Wonolog uses to log WordPress "events", considering WordPress is not natively compatible with Wonolog. In fact, Wonolog comes with a few defaults `ActionListener` that "listen to" core WordPress hooks.

The chapter *"What is logged by default"* contains all the details about "hook listeners" Wonolog ships for core, and the chapter "*Logging code not designed for Wonolog*" will document how to write such a compatibility layer for custom code.