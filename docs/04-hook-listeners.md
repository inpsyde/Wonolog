# Hook listeners

## Table of Contents

- [Introducing Hook Listeners](#introducing-hook-listeners)
- [Wonolog Hook Listeners](#wonolog-hook-listeners)


## Introducing Hook Listeners

When Wonolog is bootstrapped **without any further configuration, it starts logging some *events* that happen in the WordPress website**.

Some of these events might be PHP errors, while others might be events specific to WordPress.

The way Wonolog does it is not rocket science: it adds some callbacks to actions and filters triggered by WordPress and according to the hook fired and the arguments associated with it, some log records may be added.

This task is done by specialized objects called *hook listeners*.

Technically speaking, a hook listener is an object implementing `Inpsyde\Wonolog\HookListener\HookListenerInterface`.

Conceptually, a hook listener is an object that "listens to" one or more hooks triggered and based on some internal logic, and returns an object implementing `LogDataInterface` (see [Log record data as Wonolog objects](02-basic-wonolog-concepts#log-record-data-as-wonolog-objects)).

When this happens, the returned object gets logged.


## Wonolog Hook Listeners

Wonolog ships with a few hook listeners used to log events that happen in WordPress.

All of these are in the `Inpsyde\Wonolog\HookListener` namespace, and they are:

- `CronDebugListener`
- `DbErrorListener`
- `FailedLoginListener`
- `HttpApiListener`
- `MailerListener`
- `QueryErrorsListener`
- `WpDieHandlerListener`

Every listener is specialized in producing logs for a specific WordPress core "area", or API.

Of course, it is possible to write custom hook listeners, and actually that's the suggested way to log records using Wonolog without explicitly coupling your code to it.

Refer to [Wonolog Customization](05-wonolog-customization.md) to learn how to disable some or all of the shipped listeners, and to [Custom Hook Listeners](06-custom-hook-listeners.md) to have a look at a complete implementation of a custom hook listener and its integration with Wonolog.


-------

Read next:

- [05 - Wonolog Customization](05-wonolog-customization.md) for a deep travel through all the possible configurations available for any aspect of the package.
- [06 - Custom Hook Listeners](06-custom-hook-listeners.md) to see a complete example of a custom hook listener, its integration in Wonolog, and all the things that you need to know in order to write reusable Wonolog extensions.

Read previous: 

- [03 - A Deeper Look at Wonolog](03-a-deeper-look-at-wonolog.md) to learn more advanced concepts and features of Wonolog.
- [02 - Basic Wonolog Concepts](02-basic-wonolog-concepts.md) to learn the basics of logging with Wonolog.
- [01 - Monolog Primer](01-monolog-primer.md) to learn a bit more about Monolog core concepts.

-------

[< Back to Index](https://github.com/inpsyde/wonolog/)
