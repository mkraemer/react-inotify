# React-Inotify

Basic inotify bindings for [React PHP](https://github.com/reactphp).

##Install
This library requires PHP5.3 and the [inotify PECL extension](http://pecl.php.net/package/inotify).
The best way to install this library is through [composer](http://getcomposer.org):

```JSON
{
    "require": {
        "mkraemer/react-inotify": "1.1.0"
    }
}
```
## Usage

This library provides the Inotify class which takes an event loop and optionally the timer interval in which the inotify events should be checked as constructor arguments.
After initializing the class, you can use the add() method to add paths to the list of watched paths. Use the [inotify bitmasks](http://www.php.net/manual/en/inotify.constants.php) to define to which filesystem operations to listen.

```php
<?php

$loop = React\EventLoop\Factory::create();
$inotify = new MKraemer\ReactInotify\Inotify($loop);

$inotify->add('/tmp/', IN_CLOSE_WRITE | IN_CREATE | IN_DELETE);
$inotify->add('/var/log/', IN_CLOSE_WRITE | IN_CREATE | IN_DELETE);

$inotify->on(IN_CLOSE_WRITE, function ($path) {
    echo 'File closed after writing: '.$path.PHP_EOL;
});

$inotify->on(IN_CREATE, function ($path) {
    echo 'File created: '.$path.PHP_EOL;
});

$inotify->on(IN_DELETE, function ($path) {
    echo 'File deleted: '.$path.PHP_EOL;
});

$loop->run();

```
