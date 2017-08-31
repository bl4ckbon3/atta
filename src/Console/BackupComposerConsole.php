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

use Atta\BackupConfiguration;
use Atta\Scheduling\Scheduler;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;

/**
 * @author      Iqbal Maulana <iq.bluejack@gmail.com>
 * @created     8/30/17
 */
class BackupComposerConsole extends Command
{
    /**
     * Configure the console
     */
    protected function configure()
    {
        $this
            ->setName('backup')
            ->setDescription('Backup database by composing config')
            ->setHelp('This command allows you to backup database by composing config')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Config file', './backup-compose.yml')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $scheduler = new Scheduler();
        $configs = $this->parseConfig($input);
        
        $output->writeln('<comment>Backup running...</comment>');
        $output->writeln('');
        
        foreach ($configs as $name => $app) {
            foreach ($app['database'] as $dbName => $database) {
                $params = [
                    'app'      => $name,
                    'database' => $dbName,
                    'ext'      => $this->getOutputFileExt($database['engine']),
                    'engine'   => $database['engine'],
                ];
                
                $file = $this->getOutputFile(
                    $app['destination'],
                    $app['output_format'],
                    $app['split_directory'],
                    $params
                );
                
                $event = $scheduler->call(
                    function () use ($file, $database, $output, $app, $name, $params) {
                        $this->backup($file, $database, $output);
                        $this->purgeBackups(
                            $app['keep'],
                            $name,
                            $params['engine'],
                            $params['ext'],
                            realpath($app['destination'])
                        );
                    }
                );
                
                $event->setTimezone(new \DateTimeZone('Asia/Jakarta'));
                
                if ($app['schedule']) {
                    $event->schedule($app['schedule']);
                }
            }
        }
        
        $scheduler->run();
        
        $output->writeln('');
        $output->writeln('<info>Backup done</info>');
    }
    
    /**
     * @param string          $file
     * @param array           $database
     * @param OutputInterface $output
     *
     * @throws \Exception
     */
    private function backup($file, array $database, OutputInterface $output)
    {
        $command = $this->getApplication()->find(sprintf('backup:%s', $database['engine']));
        $arguments = [
            'command'     => sprintf('backup:%s', $database['engine']),
            'database'    => $database['database'],
            'destination' => $file,
        ];
        
        foreach (['host', 'port', 'user', 'password'] as $key) {
            if (isset($database[$key])) {
                $arguments[sprintf('--%s', $key)] = $database[$key];
            }
        }
        
        $input = new ArrayInput($arguments);
        $command->run($input, $output);
        
        @chmod($file, 0777);
    }
    
    /**
     * @param int    $keep
     * @param string $app
     * @param string $engine
     * @param string $ext
     * @param string $path
     */
    private function purgeBackups($keep, $app, $engine, $ext, $path)
    {
        if ($keep) {
            $finder = new Finder();
            
            $files = $finder->files()->name(sprintf('/^(%s_%s_)(.+).%s$/', $app, $engine, $ext));
            
            /** @var SplFileInfo[] $files */
            $files = $files->in($path)->sort(
                function (SplFileInfo $a, SplFileInfo $b) {
                    return $b->getMTime() - $a->getMTime();
                }
            )
            ;
            
            $i = 0;
            foreach ($files as $file) {
                if ($i >= $keep) {
                    @unlink($file->getRealPath());
                }
                
                $i++;
            }
        }
    }
    
    /**
     * @param InputInterface $input
     *
     * @return array
     */
    private function parseConfig(InputInterface $input)
    {
        $file = $this->getConfigFile($input);
        
        if (!file_exists($file)) {
            throw new \InvalidArgumentException(sprintf('Configuration file "%s" not found', $file));
        }
        
        $configs = Yaml::parse(file_get_contents($file));
        $processor = new Processor();
        $configuration = new BackupConfiguration();
        
        return $processor->processConfiguration($configuration, $configs);
    }
    
    /**
     * @param InputInterface $input
     *
     * @return string
     */
    private function getConfigFile(InputInterface $input)
    {
        $path = realpath(dirname($input->getOption('config'))) . DIRECTORY_SEPARATOR;
        $path = str_replace(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $path);
        
        return $path . pathinfo($input->getOption('config'), PATHINFO_BASENAME);
    }
    
    /**
     * @param string $engine
     *
     * @return string
     */
    private function getOutputFileExt($engine)
    {
        switch ($engine) {
            case 'postgres':
            case 'mysql':
                return 'sql';
            
            case 'mongo':
                return 'archive';
        }
        
        throw new \RuntimeException(sprintf('Unsupported engine "%s"', $engine));
    }
    
    /**
     * @param string $path
     * @param string $format
     * @param bool   $splitDirectory
     * @param array  $params
     *
     * @return string
     */
    private function getOutputFile($path, $format, $splitDirectory, array $params)
    {
        $language = new ExpressionLanguage();
        $path = realpath($path);
        $now = new \DateTime('now', new \DateTimeZone('Asia/Jakarta'));
        
        if (true === $splitDirectory) {
            $path = $path . DIRECTORY_SEPARATOR . $params['database'];
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
        }
        
        $file = $language->evaluate($format, array_merge($params, ['now' => $now]));
        $file = sprintf(
            '%s_%s_%s_%s.%s',
            $params['app'],
            $params['database'],
            $params['engine'],
            $file,
            $params['ext']
        );
        
        return $path . DIRECTORY_SEPARATOR . $file;
    }
}
