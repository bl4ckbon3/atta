<?php
/**
 * This file is part of the Atta package.
 *
 * (c) 2017 Media Televisi Indonesia
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Atta\Console;

/**
 * @author      Iqbal Maulana <iq.bluejack@gmail.com>
 * @created     8/30/17
 */
class MongoWorkerConsole extends AbstractDatabaseWorker
{
    /**
     * @return string
     */
    protected function getDatabaseEngine()
    {
        return 'mongo';
    }
    
    /**
     * @return int
     */
    protected function getDefaultPort()
    {
        return 27017;
    }
    
    /**
     * @return string
     */
    protected function getDefaultUser()
    {
        return null;
    }
}
