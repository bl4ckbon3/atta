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

use Atta\Dumpers;
use Spatie\DbDumper\DbDumper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author      Iqbal Maulana <iq.bluejack@gmail.com>
 * @created     8/30/17
 */
abstract class AbstractDatabaseWorker extends Command
{
    /**
     * @var DbDumper
     */
    protected $dumper;
    
    /**
     * Configure the console
     */
    protected function configure()
    {
        $engine = $this->getDatabaseEngine();
        $port = $this->getDefaultPort();
        $user = $this->getDefaultUser();
        
        $this
            ->setName(sprintf('backup:%s', $engine))
            ->setDescription(sprintf('Backup database %s', $engine))
            ->setHelp(sprintf('This command allows you to backup database %s', $engine))
            ->addArgument('database', InputArgument::REQUIRED, 'Database name to backup')
            ->addArgument('destination', InputArgument::REQUIRED, 'Destination file')
            ->addOption('host','a', InputOption::VALUE_REQUIRED, 'Database source host', '127.0.0.1')
            ->addOption('port', 'p', InputOption::VALUE_REQUIRED, 'Database port', $port)
            ->addOption('user', null, InputOption::VALUE_REQUIRED, 'Database user', $user)
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Database password')
        ;
        
        $this->dumper = (new Dumpers())->get($engine);
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $destination = $input->getArgument('destination');
        $database = $input->getArgument('database');
        $host = $input->getOption('host');
        $port = $input->getOption('port');
        $username = $input->getOption('user');
        $password = $input->getOption('password');
        $path = realpath(dirname($destination)) . DIRECTORY_SEPARATOR;
        $path = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $path);
        $file = $path . pathinfo($destination, PATHINFO_BASENAME);
        
        if (!is_writable($path)) {
            throw new \RuntimeException(sprintf('Cannot write to "%s"', $file));
        }
        
        $output->writeln(
            sprintf(
                'Backing up from <info>%s</info>...',
                sprintf('%s://%s/%s', $this->getDatabaseEngine(), $host, $database)
            )
        );
        
        $this
            ->dumper
            ->setHost($host)
            ->setPort($port)
            ->setDbName($database)
        ;
        
        if (null !== $username) {
            $this->dumper->setUserName($username);
        }
        
        if (null !== $password) {
            $this->dumper->setPassword($password);
        }
        
        $this->dumper->dumpToFile($destination);
        
        $output->writeln(sprintf('<info>Backup has been saved to </info><comment>%s</comment>', $file));
    }
    
    /**
     * @return string
     */
    abstract protected function getDatabaseEngine();
    
    /**
     * @return int
     */
    abstract protected function getDefaultPort();
    
    /**
     * @return string
     */
    abstract protected function getDefaultUser();
}
