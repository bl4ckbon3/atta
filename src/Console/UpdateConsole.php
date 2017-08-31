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

use Herrera\Phar\Update\Manager;
use Herrera\Phar\Update\Manifest;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author      Iqbal Maulana <iq.bluejack@gmail.com>
 * @created     8/31/17
 */
class UpdateConsole extends Command
{
    /**
     * Atta version repository
     */
    const MANIFEST_FILE = 'https://gitlab.metrotv.co.id/root/atta-manifest/raw/master/manifest.json';
    
    /**
     * Configure the console
     */
    protected function configure()
    {
        $this
            ->setName('update')
            ->setDescription('Updates atta to the latest version')
        ;
    }
    
    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = new Manager(Manifest::loadFile(self::MANIFEST_FILE));
        $manager->update($this->getApplication()->getVersion(), true);
    }
}
