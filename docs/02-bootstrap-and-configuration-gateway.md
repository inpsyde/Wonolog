---
title: Bootstrap and Configuration
nav_order: 30
---

# Bootstrap and Configuration

Wonolog (since v2) bootstraps itself. As soon as it is required in a project using Composer, even without any configuration, it starts working right away using its default settings.

Nevertheless, most of the time, *some* configuration is needed. [MU plugins](https://developer.wordpress.org/advanced-administration/plugins/mu-plugins/) are the place where configuration goes.

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

This approach favor Wonolog configuration from different MU plugins (or early-loaded Composer packages) and thus favor re-usable packages that configure various aspects of Wonolog.

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
