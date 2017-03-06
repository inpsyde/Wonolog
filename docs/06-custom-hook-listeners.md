# Custom hook listeners

## Table of contents

- [Custom hook listeners](#custom-hook-listeners)
- [The fictional "MyFiles" plugin](#the-fictional-myfiles-plugin)
- [Custom listener example](#custom-listener-example)
- [Integrate the custom listener](#integrate-the-custom-listener)
- [What about filters?](#what-about-filters)
- [Custom hook priority](#custom-hook-priority)


## Custom hook listeners

The suggested way to log custom code with Wonolog is to use custom hook listeners.

**Hook listeners can be seen, in fact, as Wonolog extension**, as they are reusable piece of code that integrates with 
Wonolog and extend its functionality to permit the logging of third party code.

Hook listeners allow to completely decouple the code to be logged from Wonolog, because it does not need to contain any
 `do_action( 'wonolog.log' )` call.

The only thing that is required is that code to be logged, fires hooks when "meaningful things" happen during its execution, 
allowing Wonolog to listen to those hooks and add log records.

Let's clarify with an example.


## The fictional "MyFiles" plugin

Let's assume there's a plugin named "MyFiles" that handles upload and download of files for logged-in WordPress users.

This plugin will fire some hooks:

```php
// somewhere in the plugin code...
do_action( 'myfiles_file_uploaded', $file_info, $uploader_user_id );

// ...
do_action( 'myfiles_file_upload_failed', $file_info, $failing_reson, $uploader_user_id );

// ...
do_action( 'myfiles_file_downloaded', $file_name, $downloader_user_id );
```

We will create a custom hook listener that can be used to log hooks fired by the plugin, and we will se how to integrate
the custom hook listener with Wonolog.


## Custom listener example

For the website where this plugin is installed, we can write a Wonolog hook listeners that will look more or less like 
this (the following code is PHP 5.6+):

```php
namespace MyWebiste;

use Inpsyde\Wonolog\HookListener\ActionListenerInterface;
use Inpsyde\Wonolog\Data;

class MyFilesListener implements ActionListenerInterface {
  
  const TARGET_CHANNEL_NAME = 'MyFilesPlugin';
  
  public function id() {
    return 'MyFiles Listener';
  }
  
  public function listen_to() {
    return [ 'myfiles_file_uploaded', 'myfiles_file_upload_failed', 'myfiles_file_downloaded', ];
  }  
  
  public function update( array $args ) {
    $method = [ $this, current_filter() ];
    if ( is_callable( $method ) ) {
      return $method( ...$args );
    }
  }  
  
  private function myfiles_file_uploaded( $file_info, $user_id ) {
    return new Data\Debug(
      'A file has been uploaded.',      // message
      self::TARGET_CHANNEL_NAME,        // channel
      compact( 'file_info', 'user_id' ) // context
    );
  }
  
  private function myfiles_file_upload_failed( $file_info, $reason, $user_id ) {
     return new Data\Error(
      "A file download failed because: {$reason}.",
      self::TARGET_CHANNEL_NAME,
      compact( 'file_info', 'user_id' )
    );
  }
  
  private function myfiles_file_downloaded( $file_name, $user_id ) {
    return new Data\Debug(
      'A file has been downloaded',
      self::TARGET_CHANNEL_NAME,
      compact( 'file_name', 'user_id' )
    );
  }
  
}
```

Things to note in code above:

- `listen_to()` method returns the list of all actions the listener targets
- `update()` method is called when each of those listened hooks is fired, and all the arguments passed to the hook are 
  passed as array to the method. Based on the actual hook, a different private method is then called, passing received 
  hook arguments in order (thanks to PHP 5.6 variadic arguments)
- Each of  those private methods returns an instance of Wonolog log object, that will be handled by Wonolog according to 
  its configuration (handlers, processors, channels...)
- All the log object returned, have a custom channel, 'MyFilesPlugin'. Being a custom channel, Wonolog will be able to 
  handle the log records only if it knows about it.



## Integrate the custom listener

When the lister class wrote above is available, we still have to tell Wonolog to use it.

The mu-plugin code to do that could be something like this:

```php
/*
 * Plugin name: Wonolog configuration
 */
 
use Inpsyde\Wonolog;
use Monolog\Handler\NativeMailerHandler;
use Monolog\Logger;
use MyWebiste\MyFilesListener;

if ( ! defined('Inpsyde\Wonolog\LOG') ) {
  return;
}

// add the custom channel to Wonolog
add_filter( 'wonolog.channels', function( array $channels ) {  
  $channels[] = MyFilesListener::TARGET_CHANNEL_NAME;
  
  return $channels;
} );

Wonolog\bootstrap()->use_hook_listener( new MyFilesListener() );
```



## What about filters?

When possible it is preferable to use actions to trigger log records. Because filter needs to have a return value and 
very likely we don't want that doing a log changes the return value.

However, especially if the code we want to log is not under our control or we don't want to edit it for any reason, it 
could be necessary to use filters in hook listeners.

This is why Wonolog ships  with a `FilterListenerInterface`, it can be used instead of or alongside `ActionListenerInterface` 
if listened hooks are filters.

Considering that callbacks attached to filters must return something in WordPress, and considering that  `update()` method 
returns null or log data objects, `FilterListenerInterface`  has an additional method `filter()` that receives all the 
arguments passed to filter as array (just like `update()`) and have to return the filter return value.

If you don't want the hook listener to affect result, something like:

```php
   public function filter( array $args ) {
     return $args[0];
   }
```

will do.



## Custom hook priority

Neither `ActionListenerInterface` or `FilterListenerInterface`  provide a way to specify the priority that Wonolog has 
to use to listen to hooks.

By default Wonolog uses a very late  priority, which is fine in most cases.

But we know that there are always edge cases.

For this reason, there's an additional interface: `HookPriorityInterface`.

This interface have `priority()` method that has to return the priority to use (the returned value will be used when 
Wonolog calls `add_action()` / `add_filter()` for the listened hooks).

The value returned by `priority()` will be used for **all** the listened hooks. To control the priority on a per-hook 
basis, without creating a different hook listener, Wonolog provides a filter: `'wonolog.listened-hook-priority'` that can be used for the scope.

This filter will pass to callbacks the current priority as first argument, and the hook as second, allowing to change 
priority on a per-hook basis.

The priority passed to filter callbacks is initially set to default priority used by Wonolog or to the priority returned 
by `priority()` for listeners implementing `HookPriorityInterface`.

 It means that priority can be customized even without implementing `HookPriorityInterface` but only hooking 
 `'wonolog.listened-hook-priority'`.

For example, the `MyFilesListener`  class above could do something like this:

```php
class MyFilesListener implements ActionListenerInterface {
 
   // ...
  
  public function listen_to() {
    
    $target_hooks = [
      // hook_name                 => priority
      'myfiles_file_uploaded'      => 0,
      'myfiles_file_upload_failed' => 20,
      'myfiles_file_downloaded'    => 999,
    ]
    
    add_filter( 'wonolog.listened-hook-priority', function( $priority, $hook  ) use( $target_hooks ) {
      return isset( $target_hooks[$hook] ) ? $target_hooks[$hook] : $priority;
    }, 10, 2 );
    
    return array_keys( $listened_hooks );
  }
  
   // ...
}
```


-------

Read previous: 

- [05 - Wonolog customization](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/05-wonolog-customization.md)
- [04 - Hook listeners](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/04-hook-listeners.md)
- [03 - A Deeper look at Wonolog](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/03-a-deeper-look-at-wonolog.md)
- [02 - Basic Wonolog concepts](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/02-basic-wonolog-concepts.md)
- [01 - Monolog Primer](https://github.com/inpsyde/wonolog/blob/front-controller-refactoring/docs/01-monolog-primer.md)

-------

[< Back to index](https://github.com/inpsyde/wonolog/)
