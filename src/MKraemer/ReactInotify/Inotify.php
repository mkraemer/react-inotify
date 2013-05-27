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
     * Constructor. Actual initialization takes place once first watched
     * paths is registered during add()
     *
     * @param React\EventLoop\LoopInterface $loop Event Loop
     * @see self::add()
     */
    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    /**
     * Checks all new inotify events available
     * and emits them via evenement
     */
    public function __invoke()
    {
        if (false !== ($events = \inotify_read($this->inotifyHandler))) {
            foreach ($events as $event) {
                // make sure the watch descriptor assigned to this event is
                // still valid. removing watch descriptors via 'remove()'
                // implicitly sends a final event with mask IN_IGNORE set:
                // http://php.net/manual/en/inotify.constants.php#constant.in-ignored
                if (isset($this->watchDescriptors[$event['wd']])) {
                    $path = $this->watchDescriptors[$event['wd']]['path'];
                    $this->emit($event['mask'], array($path . $event['name']));
                }
            }
        }
    }

    /**
     * Adds a path to the list of watched paths
     *
     * @param string  $path      Path to the watched file or directory
     * @param integer $mask      Bitmask of inotify constants
     * @return integer unique watch identifier, can be used to remove() watch later
     */
    public function add($path, $mask)
    {
        if ($this->inotifyHandler === false) {
            // inotifyHandler not started yet => start a new one
            $this->inotifyHandler = \inotify_init();
            stream_set_blocking($this->inotifyHandler, 0);
            
            // wait for any file events by reading from inotify handler asynchronously
            $this->loop->addReadStream($this->inotifyHandler, $this);
        }
        $descriptor = \inotify_add_watch($this->inotifyHandler, $path, $mask);
        $this->watchDescriptors[$descriptor] = array('path' => $path);
        return $descriptor;
    }
    
    /**
     * remove/cancel the given watch identifier previously aquired via add()
     * i.e. stop watching the associated path
     * 
     * @param integer $descriptor watch identifier previously returned from add()
     */
    public function remove($descriptor)
    {
        if (isset($this->watchDescriptors[$descriptor])) {
            unset($this->watchDescriptors[$descriptor]);
            
            if ($this->watchDescriptors) {
                // there are still watch paths remaining => only remove this descriptor
                \inotify_rm_watch($this->inotifyHandler, $descriptor);
            } else {
                // no more paths watched => close whole handler
                $this->close();
            }
        }
    }

    /**
     * close the inotifyHandler and clear all pending events (if any)
     */
    public function close()
    {
        if ($this->inotifyHandler !== false) {
            $this->loop->removeReadStream($this->inotifyHandler);
            
            fclose($this->inotifyHandler);
            
            $this->inotifyHandler = false;
            $this->watchDescriptors = array();
        }
    }
}
