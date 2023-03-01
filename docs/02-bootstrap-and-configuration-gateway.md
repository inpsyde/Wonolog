# Bootstrap and configuration gateway

Wonolog v2 bootstraps itself. As soon as it is required in a project using Composer, even without any configuration, it starts working right away using its default settings.

Wonolog v1 required a `bootstrap()` function to be called from a MU plugin, but that is not necessary anymore. That is probably the most significant user-facing change from v1 to v2.

Nevertheless, most of the time, *some* configuration is needed. MU plugins, even in v2, are still the place where configuration goes.

---

# Table of contents

- [The setup hook](#the-setup-hook)
    - [Monolog](#monolog)
    - [Default handler](#default-handler)
- [Wonolog writes logs](#wonolog-writes-logs)
- [Disabling Wonolog](#disabling-wonolog)

---

## The setup hook

Wonolog configuration is done by adding a callback to the **`wonolog.setup`** hook. That hook passes an instance of `Inpsyde\Wonolog\Configurator` that is the gateway to all the possible Wonolog configuration.

For example:

```php
add_action(
    'wonolog.setup',
    function (Inpsyde\Wonolog\Configurator $config) {
        $config->doNotLogPhpErrors();
    }
);
```

This new approach was introduced to favor Wonolog configuration from different MU plugins (or early loaded Composer packages) and thus favor re-usable packages that configure various aspects of Wonolog.

In v1, the necessity to call a `bootstrap()` function favorited the presence of _a_ single place for configuration, usually copy-and-pasted from project to project.

## Wonolog writes logs

As soon as it is required in a project using Composer, even without any configuration at all, Wonolog starts working right away using its default settings. It means that Wonolog not only provides abstractions and APIs for plugins/themes/packages to log *their* stuff but also *writes* logs for WordPress core "events".

The [*"What is logged by default"*](./03-what-is-logged-by-default.md) chapter explains which those "events" exactly are.

### Monolog

Anyway, to write logs, Wonolog needs a PSR-3 *implementation*, not just *abstraction*, and it uses [Monolog](https://seldaek.github.io/monolog/), the most popular PSR-3 implementation.

In Monolog, log records are written using “handlers”. Each handler is free to do anything with a log entry, and multiple handlers might process each log entry.

The nice thing about Monolog is that there are [dozens of ready-made handlers](https://seldaek.github.io/monolog/doc/02-handlers-formatters-processors.html#handlers) that, for example, write log to files, Syslog, databases, send emails or alerts, connect to logging services, and so on.

### Default handler

When Wonolog is used without any configuration, because it needs to instantiate a Monolog handler to write the log records it collects from WordPress core, it instantiates a custom handler that writes log records to file, and auto-tune its configuration to work well in WordPress context.

The chapter [*"Log records handlers"*](./06-log-records-handlers.md) have a detailed explanation on how the Wonolog default handler works.

## Disabling Wonolog

Considering Wonolog starts logging as soon as it is required, it might be desired to disable it completely, for example in some environments.

To do that programmatically, Wonolog offers 3 ways:

- `WONOLOG_DISABLE` environment variable
- `WONOLOG_DISABLE` constant
- the `wonolog.disable` filter

The list above is in order of evaluation: the value of the constant might override the value of the environment variable and the filter can be used to override the other two.


---

1. [Introduction](./00-introduction.md)
2. [Anatomy of a Wonolog log record](./01-anatomy-of-a-wonolog-log-record.md)
3. **Bootstrap and configuration gateway**
4. [What is logged by default](./03-what-is-logged-by-default.md)
5. [Designing packages for Wonolog](./04-designing-packages-for-wonolog.md)
6. [Logging code not designed for Wonolog](./05-logging-code-not-designed-for-wonolog.md)
7. [Log records handlers](./06-log-records-handlers.md)
8. [Log records processors](./07-log-records-processors.md)
9. [Custom PSR-3 loggers](./08-custom-psr-3-loggers.md)
10. [Configuration cheat sheet](./09-configuration-cheat-sheet.md)

---

« [Anatomy of a Wonolog log record](./01-anatomy-of-a-wonolog-log-record.md) || [What is logged by default](./03-what-is-logged-by-default.md) »
