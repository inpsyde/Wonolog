# Hook listeners

## Table of contents

- [Introducing hook listeners](#introducing-hook-listeners)
- [Wonolog hook listeners](#wonolog-hook-listeners)


## Introducing hook listeners

When Wonolog is bootstrapped **without any further configuration it starts logging some "events" that happen in the WordPress website**.

Some of these "events" will be PHP errors, but some other are events specific to WordPress.

The way Wonolog does it is not rocket science: it add some callbacks to actions and filters triggered by WordPress and 
according to the hook fired and the arguments associated to it, some log records may be added.

This task is done by specialized objects, called "hook listeners".

Technically speaking, an hook listener is an object implementing `Inpsyde\Wonolog\HookListener\HookListenerInterface`.

Conceptually, an hook listener is an object that "listens to" one or more hooks to be triggered and based on some internal 
logic, returns object implementing of `LogDataInterface` (see ["Log record data as Wonolog objects"](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/02-basic-wonolog-concepts#log-record-data-as-wonolog-objects)).

When this happen, the returned objects are logged.



## Wonolog hook listeners

Wonolog ships with a few "hook listeners" used to log "events" that happen in core.

All of them are in the `Inpsyde\Wonolog\HookListener` namespace and they are:

- `CronDebugListener`
- `HttpApiListener`
- `MailerListener`
- `DbErrorListener`
- `WpDieHandlerListener`
- `QueryErrorsListener`
- `FailedLoginListener`

Every listener is specialized in producing logs for a specific WordPress core "area" or "API".

Of course, it is possible to write custom hook listeners, and actually that's the suggested way to log records using 
Wonolog without coupling the code with it. 

Refers to [Wonolog customization](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/05-wonolog-customization.md), to 
learn how to disable some or all the shipped listeners and to 
[Custom hook listeners](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/06-custom-hook-listeners.md) to have a look
at a complete implementation of a custom hook listener and its integration with Wonolog.

-------

Read next:

- [05 - Wonolog customization](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/05-wonolog-customization.md)
- [06 - Custom hook listeners](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/06-custom-hook-listeners.md)

Read previous: 

- [03 - A deeper look at Wonolog](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/03-a-deeper-look-at-wonolog.md)
- [02 - Basic Wonolog concepts](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/02-basic-wonolog-concepts.md)
- [01 - Monolog Primer](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/01-monolog-primer.md)

-------

[< Back to index](https://github.com/inpsyde/wonolog/)

