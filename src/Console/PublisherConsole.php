<?php
/**
 * This file is part of the Atta package.
 *
 * (c) 2017 Media Televisi Indonesia
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Atta\Console;

use Atta\Publisher\Publisher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class PublisherConsole extends Command
{
    protected function configure()
    {
        $this
            ->setName('publish')
            ->setDescription('Publish git source to separate repository')
            ->setHelp(
                'This command allows you to split and publish git to separate repository by composing config'
            )
            ->addOption(
                'config',
                'c',
                InputOption::VALUE_REQUIRED,
                'Config File',
                './publish-compose.yml'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $basePath = $this->getConfigPath($input);
        $publisher = new Publisher($basePath);
        $configs = $publisher->parseConfig($this->getConfigs($input));

        if (!$publisher->isReady()) {
            $output->writeln(
                sprintf('<comment>[installing]</comment> third-party tools')
            );
            $publisher->install();
        }

        if (!$publisher->isInitialize()) {
            $output->writeln(
                sprintf(
                    '<comment>[cloning]</comment> %s',
                    $configs['repo']
                )
            );
            $publisher->init($configs['repo']);
        } else {
            $output->writeln(
                sprintf(
                    '<comment>[fetching]</comment> %s',
                    $configs['repo']
                )
            );
            $publisher->update();
        }

        if (count($configs['publishes'])) {
            foreach ($configs['publishes'] as $path => $publish) {
                $publisher->addToPublish($configs['heads'], $path, $publish);
            }

            $publisher->publish(
                function (int $state, string $target, string $error = null) use ($output) {
                    switch ($state) {
                        case Publisher::PUBLISH_SUCCESS:
                            $output->writeln(
                                sprintf(
                                    '<info>[published]</info> %s',
                                    $target
                                )
                            );
                            break;
                        case Publisher::PUBLISH_FAILED:
                            $output->writeln(
                                sprintf(
                                    '<error>[failed]</error> publish %s: %s',
                                    $target,
                                    $error
                                )
                            );
                            break;
                        default:
                            $output->writeln(
                                sprintf(
                                    '<comment>[publishing]</comment> %s',
                                    $target
                                )
                            );
                    }
                }
            );
        }
    }

    private function getConfigs(InputInterface $input): array
    {
        $file = $this->getConfigFile($input);

        if (!file_exists($file)) {
            throw new \InvalidArgumentException(
                sprintf('Configuration file "%s" not found', $file)
            );
        }

        $configs = Yaml::parse(file_get_contents($file));

        if (null === $configs) {
            throw new \RuntimeException(
                sprintf('Empty config detected on file "%s"', $file)
            );
        }

        return $configs;
    }

    private function getConfigFile(InputInterface $input): string
    {
        $path = $this->getConfigPath($input);

        return $path . pathinfo($input->getOption('config'), PATHINFO_BASENAME);
    }

    private function getConfigPath(InputInterface $input): string
    {
        $path = realpath(dirname($input->getOption('config')))
            . DIRECTORY_SEPARATOR;

        return str_replace(
            DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR,
            DIRECTORY_SEPARATOR,
            $path
        );
    }
}
