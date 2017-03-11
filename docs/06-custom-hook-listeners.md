# Custom Hook Listeners

## Table of Contents

- [Custom Hook Listeners](#custom-hook-listeners)
- [The Fictional "MyFiles" Plugin](#the-fictional-myfiles-plugin)
- [Custom Listener Example](#custom-listener-example)
- [Integrating a Custom Listener](#integrating-a-custom-listener)
- [What about Filters?](#what-about-filters)
- [Custom Hook Priority](#custom-hook-priority)


## Custom Hook Listeners

The suggested way to log custom code with Wonolog is to use custom hook listeners.

**Hook listeners can be seen, in fact, as Wonolog extensions**, as they are reusable pieces of code that integrate with Wonolog and extend its functionality to permit the logging of third-party code.

Hook listeners allow to completely decouple the code to be logged from Wonolog, because it does not need to contain any `do_action( 'wonolog.log' )` call.

The only thing that is required is that the code to be logged fires actions when "meaningful things" happen during execution, allowing Wonolog to listen to these actions and create log records.

Let's clarify with an example.


## The Fictional "MyFiles" Plugin

Let's assume there's a plugin named "MyFiles" that handles upload and download of files for logged-in WordPress users.

This plugin will fire some actions:

```php
// Somewhere in the plugin code:
do_action( 'myfiles_file_uploaded', $file_info, $uploader_user_id );

// Somewhere in the plugin code:
do_action( 'myfiles_file_upload_failed', $file_info, $reason, $uploader_user_id );

// Somewhere in the plugin code:
do_action( 'myfiles_file_downloaded', $file_name, $downloader_user_id );
```

We will create a custom hook listener that can be used to log actions fired by the plugin, and we will se how to integrate the custom hook listener with Wonolog.


## Custom Listener Example

For the website where this plugin is installed, we can write a Wonolog hook listener that will look more or less like this (please note that the following code is PHP 5.6+):

```php
namespace MyWebiste;

use Inpsyde\Wonolog\Data;
use Inpsyde\Wonolog\HookListener\ActionListenerInterface;

class MyFilesListener implements ActionListenerInterface {

    const TARGET_CHANNEL_NAME = 'MyFilesPlugin';

    public function id() {

        return 'MyFiles Listener';
    }

    public function listen_to() {

        return [
            'myfiles_file_uploaded',
            'myfiles_file_upload_failed',
            'myfiles_file_downloaded',
        ];
    }  

    public function update( array $args ) {

        $method = [ $this, current_filter() ];
        if ( is_callable( $method ) ) {
            return $method( ...$args );
        }
    }  

    private function myfiles_file_uploaded( $file_info, $user_id ) {

        return new Data\Debug(
            'A file has been uploaded.',      // Message.
            self::TARGET_CHANNEL_NAME,        // Channel.
            compact( 'file_info', 'user_id' ) // Context.
        );
    }

    private function myfiles_file_upload_failed( $file_info, $reason, $user_id ) {

        return new Data\Error(
            "A file download failed: {$reason}.",
            self::TARGET_CHANNEL_NAME,
            compact( 'file_info', 'user_id' )
        );
    }

    private function myfiles_file_downloaded( $file_name, $user_id ) {

        return new Data\Debug(
            'A file has been downloaded.',
            self::TARGET_CHANNEL_NAME,
            compact( 'file_name', 'user_id' )
        );
    }
}
```

Things to note in the above code:

- The `listen_to()` method returns the list of all actions the listener targets.
- The `update()` method is called when each of those listened hooks is fired, and all the arguments passed to the hook are passed as array to the method.
Based on the actual hook, a different private method is called, passing received hook arguments in order (thanks to PHP 5.6 variadic arguments).
- Each of  those private methods returns a Wonolog log object that will be handled by Wonolog according to its configuration (i.e., handlers, processors, channels etc.).
- All log objects returned have a custom channel, 'MyFilesPlugin', so Wonolog will have to _know_ about it to be able to handle the log records.


## Integrating a Custom Listener

With the listener class from above available, we still have to tell Wonolog to use it.

The MU plugin to do that could be something like this:

```php
/*
 * Plugin name: Wonolog Configuration
 */
 
use Inpsyde\Wonolog;
use MyWebsite\MyFilesListener;

if ( ! defined( 'Inpsyde\Wonolog\LOG' ) ) {
    return;
}

// Add the custom channel to Wonolog.
add_filter( 'wonolog.channels', function( array $channels ) {  

    $channels[] = MyFilesListener::TARGET_CHANNEL_NAME;

    return $channels;
} );

Wonolog\bootstrap()->use_hook_listener( new MyFilesListener() );
```


## What about Filters?

When possible, it is preferable to use **actions** to trigger log records.
Filters need to return a value, and we most probably do not want that logging changes the return value.

However, especially if the code we want to log is not under our control or we don't want to edit it for any reason, it could be necessary to use filters in hook listeners.

This is why Wonolog ships with a `FilterListenerInterface` that can be used instead of, or alongside, `ActionListenerInterface`.

Considering that callbacks attached to filters must return something in WordPress, and considering that the `update()` method returns `null` or log data objects, `FilterListenerInterface` has an additional method `filter()` that receives all the arguments passed to filter as array (just like `update()`) and **has to return the filter return value**.

If you do not want the hook listener to affect the filtering, something like this will do already:

```php
public function filter( array $args ) {

    return $args[0];
}
```


## Custom Hook Priority

Neither `ActionListenerInterface`, nor `FilterListenerInterface` provide a way to specify the priority that Wonolog uses to listen to hooks.

By default, Wonolog uses a very late priority, which is fine in most cases.

But we know that there are always edge cases.

For this reason, there's an additional interface: `HookPriorityInterface`.

This interface has a `priority()` method that has to return the priority to use (the returned value will be used when Wonolog calls `add_action()` and `add_filter()`, respectively).

The value returned by `priority()` will be used for **all** hooks listened to.
To control the priority on a per-hook basis, without creating a different hook listener, Wonolog provides a filter, `'wonolog.listened-hook-priority'`, that can be used for the scope.

This filter will provide callbacks with the both the current priority as first argument, and the hook as second, allowing to change priority on a per-hook basis.

The priority passed to filter callbacks is initially set to the default priority used by Wonolog, or to the priority returned by `priority()` for listeners implementing `HookPriorityInterface`.

This means that priority can be customized even without implementing `HookPriorityInterface` but only hooking `'wonolog.listened-hook-priority'`.

For example, the `MyFilesListener` class from above could do something like this:

```php
class MyFilesListener implements ActionListenerInterface {
 
    // ...

    public function listen_to() {

        $target_hooks = [
            // hook_name                 => priority
            'myfiles_file_uploaded'      => 0,
            'myfiles_file_upload_failed' => 20,
            'myfiles_file_downloaded'    => 999,
         ];

        add_filter( 'wonolog.listened-hook-priority', function( $priority, $hook  ) use ( $target_hooks ) {

            return isset( $target_hooks[ $hook ] ) ? $target_hooks[ $hook ] : $priority;
        }, 10, 2 );

        return array_keys( $target_hooks );
    }

    // ...
}
```


-------

Read previous: 

- [05 - Wonolog Customization](05-wonolog-customization.md) for a deep travel through all the possible configurations available for any aspect of the package.
- [04 - Hook Listeners](04-hook-listeners.md) to read about hook listeners, the powerful feature of Wonolog that allows for logging any WordPress code.
- [03 - A Deeper Look at Wonolog](03-a-deeper-look-at-wonolog.md) to learn more advanced concepts and features of Wonolog.
- [02 - Basic Wonolog Concepts](02-basic-wonolog-concepts.md) to learn the basics of logging with Wonolog.
- [01 - Monolog Primer](01-monolog-primer.md) to learn a bit more about Monolog core concepts.

-------

[< Back to Index](https://github.com/inpsyde/wonolog/)
