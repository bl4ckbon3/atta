<?php
/**
 * This file is part of the Atta package.
 *
 * (c) 2017 Media Televisi Indonesia
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Atta;

use Spatie\DbDumper\Databases\MongoDb;
use Spatie\DbDumper\Databases\MySql;
use Spatie\DbDumper\Databases\PostgreSql;
use Spatie\DbDumper\DbDumper;

/**
 * @author      Iqbal Maulana <iq.bluejack@gmail.com>
 * @created     8/30/17
 */
class Dumpers
{
    /**
     * @var array
     */
    private static $dumpers = [
        'postgres' => PostgreSql::class,
        'mysql'    => MySql::class,
        'mongo'    => MongoDb::class,
    ];
    
    /**
     * @param string $engine
     *
     * @return DbDumper
     */
    public function get($engine)
    {
        if (!$this->support($engine)) {
            throw new \RuntimeException(sprintf('Engine "%s" currently is not supported'));
        }
        
        return self::$dumpers[$engine]::{'create'}();
    }
    
    /**
     * @param string $engine
     *
     * @return bool
     */
    public function support($engine)
    {
        return isset(self::$dumpers[$engine]);
    }
}
