# Monolog Primer

## TOC

- [Monolog concepts](#monolog-concepts)
- [Loggers and handlers](#loggers-and-handlers)
- [Log processors](#log-processors)
- [Monolog record processing workflow](#monolog-record-processing-workflow)


## Monolog concepts

Wonolog is a sort of "bridge" between Monolog and WordPress.

To get the best out of Wonolog, the understanding of some Monolog "basics" are required.

It is strongly suggested to read the [Monolog documentation about its core concepts](https://github.com/Seldaek/monolog/blob/master/doc/01-usage.md#core-concepts) to get a better understanding of the library.



## Loggers and handlers

The main objects in Monolog are "loggers". 

Every logger as a "channel" and one or more "handlers".

The channel is just a name for the logger, and let's you identify the _kind_ of event the logger handles.

For example, you may have logger for security logs, with a channel name of "Security" and a logger for database errors with a channel name of "Database" and so on.

Handlers are objects that actually "write" the log "somewhere".

The awesome about Monolog is that it comes with [a lot of ready made handlers](https://github.com/Seldaek/monolog/blob/master/doc/02-handlers-formatters-processors.md#handlers) that covers a lot of use cases.

There are handlers that write logs to files, to generic streams, to emails, third party services...

Please refer to [Monolog documentation](https://github.com/Seldaek/monolog/blob/master/doc/02-handlers-formatters-processors.md) for more information and for the list of supported handlers.

And refers to [Wonolog customization](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/05-wonolog-customization.md), to explore the ways Wonolog exposes Monolog logger objects, making very easy to make use of all of ready made or even custom handlers.

Every Monolog handler comes with:

- one or more "log processors"
- a minimum "log level"
- a "bubble" property



## Log processors

In Monolog every log record has a "raw" representation, that takes the form of an array.

It contains log record basic data (message, channel, level, context, date and time). May be desirable to customize what 
a record contain. This customization can be done in a programmatic way using "processors".

A processor is no more than a callback that receives the log record array, processes it and returns the processed log record.

Processor can be added at logger level (all the records in a channel will be processed) or at handler level (all the 
records processed by a specific handler will be processed).

For example, you may want to add some context to all the logs of a channel, but strip sensitive data from log record 
handled by an handler that sends data to a third party service.

Please refer to [Monolog documentation](https://github.com/Seldaek/monolog/blob/master/doc/01-usage.md) for more info on processors.

**Wonolog ships with a default log processor that is applied by default to all the log records.**

This processor adds to the record some context regarding WordPress status when the record was created. 

It will, in fact, add information about the kind of request (admin, AJAX, REST or XML_RPC), if multisite or not 
(and when multisite, the current site ID is added besides other multisite-specific context) and when possible the ID of 
current logged in user.

Just like any other Wonolog feature this default processor can be customized or even disabled.

Please refers to [Wonolog customization](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/05-wonolog-customization.md)



## Log levels

In WordPress there's a "binary" setting for logging: `WP_DEBUG_LOG`. It is a constant that can be either `true` or `false`,
so the log can be turned on or off.

Monolog (and thus Wonolog) supports different "levels" of logging and **each handler can be set with a minimum level**, 
independently from other handlers.

For example, there might be an email handler that sends a message when an "emergency" error happen, but does nothing  
when a less critical event happen, while at same time a file handler write any message to a log file, no matter the level.

Monolog log levels are inherited from [PSR-3 standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md#5-psrlogloglevel), 
and can conveniently be accessed as class constants of `Monolog\Logger` class.

They are (in descending order of severity):

- `Logger::EMERGENCY`
- `Logger::ALERT`
- `Logger::CRITICAL`
- `Logger::ERROR`
- `Logger::WARNING`
- `Logger::NOTICE`
- `Logger::INFO`
- `Logger::DEBUG`


## Monolog record processing workflow

The workflow Monolog uses to process log record is:

1. A record array is created
2. Based on the record channel, record is assigned to a logger
3. All processors assigned to the logger are executed and the obtained array is then passed to the first handler assigned 
   to the logger
4. The first handler, will decides if handle the record or not, comparing record level with its minimum level.
   If the handler determinates it has to handle the record, all processors assigned to the handler will process the record before actually handling it
5. After a log has been handled by an handler, if the handler "bubble" property is `true` the record is passed to next
   handler, and set _4._ is repeated again, until there are no more handlers assigned to the logger or until the first handler with "bubble" property set to false is encountered


-------

Read next:

- [02 - Basic Wonolog concepts](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/02-basic-wonolog-concepts.md)
- [03 - A deeper look at Wonolog](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/03-a-deeper-look-at-wonolog.md)
- [04 - Hook listeners](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/04-hook-listeners.md)
- [05 - Wonolog customization](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/05-wonolog-customization.md)
- [06 - Custom hook listeners](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/06-custom-hook-listeners.md)

-------

[< Back to index](https://github.com/inpsyde/wonolog/)