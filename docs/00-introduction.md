# Introduction

![Wonolog Logo](../resources/banner.png)

Wonolog is a package whose scope is logging "things" that happen in WordPress and WordPress plugins/themes/packages.

Logging means *to persist somewhere* information. When developing a plugin/theme/library, the infrastructure where the code runs is unknown, it is only known at *the website* level. That is why **Wonolog is a package to be used at the website level**.

**Wonolog does two things:**

- logs "things" that happen in WordPress (PHP errors and exceptions, 404 errors, cron jobs, HTTP API calls, etc...)
- integrates with plugins/themes/packages to log *their* "things"

---

# Table of contents

- [Logging WordPress](#logging-wordpress)
- [Logging other code](#logging-other-code)
    - [Logging code "natively compatible" with Wonolog](#logging-code-natively-compatible-with-wonolog)
    - [Logging code not "natively compatible" with Wonolog](#logging-code-not-natively-compatible-with-wonolog)

---

## Logging WordPress

As soon as Wonolog is installed, it starts working, even without any configuration at all.

The chapter [*"What is logged by default"*](./03-what-is-logged-by-default.md) contains all the details on *what* exactly is logged, and the chapter [*Bootstrap and configuration gateway"*](./02-bootstrap-and-configuration-gateway.md) will describe and *where* and *how* those logs are saved by default.

The rest of documentation will show how configurable Wonolog is. It's straightforward, for example, to granularly configure *what* it logs, and thanks to [Monolog](https://seldaek.github.io/monolog/) under its hood, there are countless possibilities about *how* and *where* to save logs.

## Logging other code

When it comes to log code that is *not* WordPress core, there are two possibilities:

- code that it is designed to be compatible with Wonolog
- code that is not compatible with Wonolog

### Logging code "natively compatible" with Wonolog

"*Natively compatible with* Wonolog" does not mean *dependent on* Wonolog. In fact, it is possible, recommended actually, to write plugins/themes/packages that do **not** depend on it.

Wonolog advocates a pattern in which plugins/themes/packages do not directly *write* logs but "emit" *events* that represent log records, leaving the burden of "listening to" those *events* and writing log records to code that is aware of the infrastructure. Wonolog is that code.

The chapter [*"Design packages for Wonolog"*](./04-designing-packages-for-wonolog.md) provides all the details about writing packages natively compatible with Wonolog, but here's a introduction on the two possible approaches.

#### The "WordPressy way"

When talking of WordPress code, to "emit events" can be accomplished without custom code or 3rd party dependencies by **firing an action hook**. Having action hooks designed specifically to emit log "events" is enough to make any WordPress code "natively compatible" with Wonolog.

#### The "PSR way"

Wonolog uses [Monolog](https://seldaek.github.io/monolog/) under the hood, and Monolog is the most popular implementation of [the PSR-3 standard](https://www.php-fig.org/psr/psr-3/). This means that Wonolog is itself a package that provides a PSR-3 implementation. That's something that can be leveraged in plugins/themes/packages/etc.

### Logging code not "natively compatible" with Wonolog

When dealing with code we can't or don't want to change, the "one line" integration with Wonolog is impossible.

However, because we are talking about *WordPress* code, it **will** use hooks, but they will not be "logging hooks". What we need is a "compatibility layer" that listens to those "generic" WordPress hooks and map them to "loggable objects".

The "compatibility layer" in Wonolog is represented by "**hook listeners**": objects that, as the name suggests, listen to hooks use Wonolog to perform logs.

Please note that is precisely the approach Wonolog uses to log WordPress "events", considering WordPress is not natively compatible with Wonolog.

The chapter [*"What is logged by default"*](./03-what-is-logged-by-default.md) contains all the details about how Wonolog logs core events, and the chapter [*"Logging code not designed for
Wonolog"*](./05-logging-code-not-designed-for-wonolog.md) will document how to write a similar "compatibility layer" for custom code.


---

1. **Introduction**
2. [Anatomy of a Wonolog log record](./01-anatomy-of-a-wonolog-log-record.md)
3. [Bootstrap and configuration gateway](./02-bootstrap-and-configuration-gateway.md)
4. [What is logged by default](./03-what-is-logged-by-default.md)
5. [Designing packages for Wonolog](./04-designing-packages-for-wonolog.md)
6. [Logging code not designed for Wonolog](./05-logging-code-not-designed-for-wonolog.md)
7. [Log records handlers](./06-log-records-handlers.md)
8. [Log records processors](./07-log-records-processors.md)
9. [Custom PSR-3 loggers](./08-custom-psr-3-loggers.md)
10. [Configuration cheat sheet](./09-configuration-cheat-sheet.md)

---

[Anatomy of a Wonolog log record](./01-anatomy-of-a-wonolog-log-record.md) »
