# Monolog Primer

## Table of Contents

- [Monolog Concepts](#monolog-concepts)
- [Loggers and Handlers](#loggers-and-handlers)
- [Log Processors](#log-processors)
- [Monolog Record Processing Workflow](#monolog-record-processing-workflow)


## Monolog Concepts

Wonolog is a sort of "bridge" between WordPress and Monolog.

To get the best out of Wonolog, the understanding of some Monolog basics are required.

It is strongly suggested to read the [Monolog documentation about its core concepts](https://github.com/Seldaek/monolog/blob/master/doc/01-usage.md#core-concepts) to get a better understanding of the library.


## Loggers and Handlers

The main objects in Monolog are *loggers*. 

Every logger as a *channel* and one or more *handlers*.

The channel is just a name for the logger, and it let's you identify the _kind_ of events that the logger handles.

For example, you may have a logger for security issues, with a channel name of "Security", and a logger for database errors, with a channel name of "Database" and so on.

Handlers are objects that actually "write" the log *somewhere*.

Monolog already comes with [a lot of ready-made handlers](https://github.com/Seldaek/monolog/blob/master/doc/02-handlers-formatters-processors.md#handlers) that cover a lot of common use cases.

There are handlers that write logs to files, to generic streams, send emails, integrate with third party services, ...

Please refer to the [Monolog documentation](https://github.com/Seldaek/monolog/blob/master/doc/02-handlers-formatters-processors.md) for more information and for the list of supported handlers.

And refer to [Wonolog Customization](05-wonolog-customization.md) to explore the ways Wonolog exposes Monolog logger objects, making it very easy to utilize all (possibly custom) handlers.

Every Monolog handler comes with:

- one or more *log processors*;
- a minimum *log level*;
- a *bubble* property.


## Log Processors

In Monolog, every log record has a "raw" representation, that takes the form of an array.

It contains basic log record data (i.e., message, channel, level, context, date and time).
Sometimes it is desirable to customize what a record contains.
This customization can be done in a programmatic way using *processors*.

A processor is no more than a callback that receives the log record array, processes it and returns the processed log record.

Processor can be added at logger level (i.e., all records in a channel will be processed) or at handler level (i.e., only the records of a specific handler will be processed).

For example, you may want to add some context to all the logs of a channel, but strip sensitive data from log record handled by an handler that sends data to a third-party service.

Please refer to the [Monolog documentation](https://github.com/Seldaek/monolog/blob/master/doc/01-usage.md) for more information on processors.

**Wonolog ships with a default log processor that is applied by default to all log records.**

This processor adds to each record the context regarding the WordPress status at the time the record was created. 

It will, in fact, add information about the kind of request (i.e., admin, AJAX, REST or XML-RPC), whether or not this is a multisite installation (and, when multisite, other multisite-specific information such as the current site ID) and the ID of the current user, if logged in.

Just like any other Wonolog feature, this default processor can be customized, or even disabled.

Please refer to [Wonolog Customization](05-wonolog-customization.md).


## Log Levels

In WordPress, there is a "binary" setting for logging: `WP_DEBUG_LOG`.
It is a constant that can be either `true` or `false`, so the log can be turned on or off.

Monolog (and thus Wonolog) supports different *levels* of logging, and **each handler can be set with a minimum level**, independently from other handlers.

For example, there might be an email handler that sends a message when an "emergency" error happen, but does nothing when a less critical event happens.
At the same time, a file handler writes **any** message to a log file, no matter what level.

Monolog log levels are inherited from [PSR-3 standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md#5-psrlogloglevel), and can conveniently be accessed as class constants of the `Monolog\Logger` class.

They are (in descending order of severity):

- `Logger::EMERGENCY`
- `Logger::ALERT`
- `Logger::CRITICAL`
- `Logger::ERROR`
- `Logger::WARNING`
- `Logger::NOTICE`
- `Logger::INFO`
- `Logger::DEBUG`


## Monolog Record Processing Workflow

This is the workflow Monolog uses to process a log record:

1. A record array is created.
1. Based on the record channel, the record is assigned to a logger.
1. All processors assigned to the logger are executed and the obtained array is then passed to the first handler assigned to the logger.
1. The first handler will *decide* whether or not to handle the record, comparing the record level with its own minimum level.
If the handler determines it has to handle the record, all processors assigned to the handler will process the record before the handler actually handles it.
1. After a log has been handled by a handler with the "bubble" property set to `true`, the record is passed on to the next handler. This is repeated until there are no more handlers assigned to the logger, or until a handler with the "bubble" property set to `false` was encountered.


-------

Read next:

- [02 - Basic Wonolog Concepts](02-basic-wonolog-concepts.md) to learn the basics of logging with Wonolog.
- [03 - A Deeper Look at Wonolog](03-a-deeper-look-at-wonolog.md) to learn more advanced concepts and features of Wonolog.
- [04 - Hook Listeners](04-hook-listeners.md) to read about hook listeners, the powerful feature of Wonolog that allows for logging any WordPress code.
- [05 - Wonolog Customization](05-wonolog-customization.md) for a deep travel through all the possible configurations available for any aspect of the package.
- [06 - Custom Hook Listeners](06-custom-hook-listeners.md) to see a complete example of a custom hook listener, its integration in Wonolog, and all the things that you need to know in order to write reusable Wonolog extensions.

-------

[< Back to Index](https://github.com/inpsyde/Wonolog/)
