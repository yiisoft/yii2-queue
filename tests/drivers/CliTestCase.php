<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace tests\drivers;

use ReflectionClass;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use tests\app\PriorityJob;

/**
 * CLI Test Case.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class CliTestCase extends TestCase
{
    /**
     * @var Process[] ids of started processes
     */
    private array $processes = [];

    /**
     * @param array $cmd
     */
    protected function runProcess(array $cmd): void
    {
        $cmd = $this->prepareCmd($cmd);
        $process = new Process($cmd);
        $process->mustRun();

        $error = $process->getErrorOutput();
        $this->assertEmpty(
            $error,
            "Can not execute " . implode($cmd) . " command:\n$error"
        );
    }

    /**
     * @param array $cmd
     * @return Process
     */
    protected function startProcess(array $cmd): Process
    {
        $process = new Process($this->prepareCmd($cmd));
        $process->start();
        sleep(2);
        if ($process->getExitCode() !== null) {
            throw new ProcessFailedException($process);
        }
        $this->processes[] = $process;
        return $process;
    }

    /**
     * @param array $cmd
     * @return array
     */
    private function prepareCmd(array $cmd): array
    {
        $class = new ReflectionClass($this->getQueue());
        $method = $class->getMethod('getCommandId');

        $replace = [
            'php' => PHP_BINARY,
            'yii' => __DIR__ . '/../yii',
            'queue' => $method->invoke($this->getQueue()),
        ];

        array_walk(
            $cmd,
            static function (&$v) use ($replace) {
                $v = strtr((string)$v, $replace);
            }
        );

        return $cmd;
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        if (file_exists(PriorityJob::getFileName())) {
            unlink(PriorityJob::getFileName());
        }

        // Kills started processes
        foreach ($this->processes as $process) {
            $process->stop();
        }
        $this->processes = [];

        parent::tearDown();
    }
}
