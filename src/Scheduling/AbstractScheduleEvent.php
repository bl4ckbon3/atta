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

use Cron\CronExpression;

/**
 * @author      Iqbal Maulana <iq.bluejack@gmail.com>
 * @created     8/30/17
 */
abstract class AbstractScheduleEvent
{
    /**
     * The cron expression representing the event's frequency.
     *
     * @var string
     */
    protected $expression = '* * * * * *';
    
    /**
     * @var \DateTimeZone
     */
    protected $timezone;
    
    /**
     * @param string $expression
     *
     * @return $this
     */
    public function schedule($expression)
    {
        $this->expression = $expression;
        
        return $this;
    }
    
    /**
     * @param \DateTimeZone $timezone
     *
     * @return $this
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;
        
        return $this;
    }
    
    /**
     * @return bool
     */
    public function isDue()
    {
        $now = new \DateTime();
        
        if ($this->timezone) {
            $now->setTimezone($this->timezone);
        }
        
        return CronExpression::factory($this->expression)->isDue($now->format('Y-m-d H:i:s'));
    }
    
    abstract public function run();
}
