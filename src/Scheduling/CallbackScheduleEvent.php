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
class CallbackScheduleEvent extends AbstractScheduleEvent
{
    /**
     * @var callable
     */
    private $callback;
    
    /**
     * Constructor.
     *
     * @param callable $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }
    
    public function run()
    {
        call_user_func($this->callback);
    }
}
