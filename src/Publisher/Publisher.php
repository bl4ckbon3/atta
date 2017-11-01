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

namespace Atta\Publisher;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class Publisher
{
    public const PUBLISH_INFO = 0;

    public const PUBLISH_SUCCESS = 1;

    public const PUBLISH_FAILED = 2;

    private const SUB_SPLIT_REPO = 'https://github.com/dflydev/git-subsplit.git';

    /**
     * @var string
     */
    private $gitExecutablePath;

    /**
     * @var string
     */
    private $basePath;

    /**
     * @var Process[]
     */
    private $publishing = [];

    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
    }

    public function publish(callable $callback = null): void
    {
        if (empty($this->publishing)) {
            throw new \RuntimeException('There are no to be publish');
        }

        foreach ($this->publishing as $target => $publish) {
            if (!$publish->isRunning()) {
                $callback(
                    self::PUBLISH_INFO,
                    $target
                );
                $publish->start();
            }
        }

        do {
            foreach ($this->publishing as $target => $publish) {
                if ($publish->isRunning()) {
                    continue;
                }

                unset($this->publishing[$target]);
                $error = trim($publish->getErrorOutput());

                if (!empty($error) && preg_match('/fatal|error/', $error)) {
                    $parts = explode("\n", $error);
                    $error = end($parts);

                    $callback(
                        self::PUBLISH_FAILED,
                        $target,
                        $error
                    );
                } else {
                    $callback(
                        self::PUBLISH_SUCCESS,
                        $target
                    );
                }
            }
        } while (!empty($this->publishing));
    }

    public function addToPublish(string $branch, string $path, string $repo, ?int $timeout = 300): void
    {
        $builder = new ProcessBuilder(
            [
                'git',
                'subsplit',
                'publish',
                '--heads=' . $branch,
                $path . ':' . $repo,
            ]
        );
        $builder->setTimeout($timeout);
        $builder->setWorkingDirectory($this->basePath);
        $process = $builder->getProcess();

        $key = rtrim($path, '/') . ' => ' . rtrim($repo, '/');
        $this->publishing[$key] = $process;
    }

    public function parseConfig(array $configs): array
    {
        $configs = ['publisher' => $configs];
        $processor = new Processor();
        $configuration = new PublishConfiguration();

        return $processor->processConfiguration($configuration, $configs);
    }

    public function isReady(): bool
    {
        $path = $this->getGitExecutablePath();

        return file_exists(sprintf('%s/git-subsplit', $path));
    }

    public function isInitialize(): bool
    {
        return is_dir(sprintf('%s/.subsplit', $this->basePath));
    }

    public function init(string $repo): void
    {
        $builder = new ProcessBuilder(
            [
                'git',
                'subsplit',
                'init',
                $repo,
            ]
        );
        $builder->setWorkingDirectory($this->basePath);

        $this->execute($builder->getProcess());
    }

    public function update(): void
    {
        $builder = new ProcessBuilder(
            [
                'git',
                'subsplit',
                'update',
            ]
        );
        $builder->setWorkingDirectory($this->basePath);

        $this->execute($builder->getProcess());
    }

    public function install(): void
    {
        $installerPathName = 'subsplit-installer';
        $tmp = sys_get_temp_dir();
        $installerPath = sprintf('%s/%s', $tmp, $installerPathName);

        if (is_dir($installerPath)) {
            $this->removeDir($installerPath);
        }

        $this->clone($installerPathName, self::SUB_SPLIT_REPO, $tmp);

        $builder = new ProcessBuilder(['./install.sh']);
        $builder->setWorkingDirectory($installerPath);

        $this->execute($builder->getProcess());

        $this->removeDir($installerPath);
    }

    private function clone(string $pathName, string $repo, string $target): void
    {
        $builder = new ProcessBuilder(
            [
                'git',
                'clone',
                $repo,
                $pathName,
            ]
        );
        $builder->setWorkingDirectory($target);

        $this->execute($builder->getProcess());
    }

    private function getGitExecutablePath(): string
    {
        if (null !== $this->gitExecutablePath) {
            return $this->gitExecutablePath;
        }

        $process = new Process('git --exec-path');

        $this->execute($process);

        return $this->gitExecutablePath = trim($process->getOutput());
    }

    private function removeDir(string $path): void
    {
        $builder = new ProcessBuilder(['rm', '-rf', $path]);

        $this->execute($builder->getProcess());
    }

    private function execute(Process $process): void
    {
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}
