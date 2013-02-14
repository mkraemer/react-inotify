<?php

namespace MKraemer\ReactInotify;

use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;

/**
 * Inotify allows to listen to inotify events emit evenement events
 */
class Inotify extends EventEmitter
{
    /**
     * @var resource|false
     */
    protected $inotifyHandler = false;

    /**
     * @var array
     */
    protected $watchDescriptors = array();
    
    /**
     * @var LoopInterface
     */
    protected $loop;
    
    /**
     * @var float
     */
    protected $interval;
    
    /**
     * @var string
     */
    protected $timerId;

    /**
     * Constructor. Initializes the inotifyHandler and
     * registers a periodicTimer in the eventLoop
     *
     * @param React\EventLoop\LoopInterface $loop     Event Loop
     * @param float                         $interval Interval in which new events should be read
     */
    public function __construct(LoopInterface $loop, $interval = 0.1)
    {
        $this->loop = $loop;
        $this->interval = $interval;
    }

    /**
     * Checks if new inotify events are available
     * and emits them via evenement
     */
    public function __invoke()
    {
        if (false !== ($events = \inotify_read($this->inotifyHandler))) {
            foreach ($events as $event) {
                $path = $this->watchDescriptors[$event['wd']]['path'];
                $this->emit($event['mask'], array($path . $event['name']));
            }
        }
    }

    /**
     * Adds a path to the list of watched paths
     *
     * @param string  $path      Path to the watched file or directory
     * @param integer $mask      Bitmask of inotify constants
     */
    public function add($path, $mask)
    {
        if ($this->inotifyHandler === false) {
            // inotifyHandler not started yet => start a new one
            $this->inotifyHandler = \inotify_init();
            stream_set_blocking($this->inotifyHandler, 0);
            
            $this->timerId = $this->loop->addPeriodicTimer($this->interval, $this);
        }
        $descriptor = \inotify_add_watch($this->inotifyHandler, $path, $mask);
        $this->watchDescriptors[$descriptor] = array('path' => $path);
    }

    /**
     * close the inotifyHandler and clear all pending events (if any)
     */
    public function close()
    {
        if ($this->inotifyHandler !== false) {
            $this->loop->cancelTimer($this->timerId);
            
            fclose($this->inotifyHandler);
            
            $this->inotifyHandler = false;
            $this->watchDescriptors = array();
        }
    }
}
