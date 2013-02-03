<?php

require __DIR__.'/../vendor/autoload.php';

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
