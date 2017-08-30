<?php
/**
 * This file is part of the Atta package.
 *
 * (c) 2017 Media Televisi Indonesia
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Atta\Scheduling;

/**
 * @author      Iqbal Maulana <iq.bluejack@gmail.com>
 * @created     8/30/17
 */
class Scheduler
{
    /**
     * @var AbstractScheduleEvent[]
     */
    protected $events = [];
    
    /**
     * @param callable $callback
     *
     * @return CallbackScheduleEvent
     */
    public function call(callable $callback)
    {
        $this->events[] = $event = new CallbackScheduleEvent($callback);
        
        return $event;
    }
    
    public function run()
    {
        foreach ($this->dueEvents() as $event) {
            $event->run();
        }
    }
    
    /**
     * @return AbstractScheduleEvent[]
     */
    public function dueEvents()
    {
        return array_filter($this->events, function(AbstractScheduleEvent $event) {
            return $event->isDue();
        });
    }
}
