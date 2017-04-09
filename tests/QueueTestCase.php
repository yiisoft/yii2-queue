<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace tests;

use Symfony\Component\Process\Process;
use Yii;
use tests\app\TestJob;
use zhuravljov\yii\queue\Queue;

/**
 * Queue Test Case
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class QueueTestCase extends TestCase
{
    /**
     * @var Process[] ids of started processes
     */
    private $processes = [];

    /**
     * @return Queue
     */
    abstract protected function getQueue();

    /**
     * @param string $cmd
     * @return string
     */
    private function prepareCmd($cmd)
    {
        $class = new \ReflectionClass($this->getQueue());
        $method = $class->getMethod('getCommandId');
        $method->setAccessible(true);

        return strtr($cmd, [
            'queue' => $method->invoke($this->getQueue()),
        ]);
    }

    /**
     * @param string $cmd
     */
    protected function runProcess($cmd)
    {
        $cmd = $this->prepareCmd($cmd);
        $process = new Process($cmd);
        $process->run();

        $error = $process->getErrorOutput();
        $this->assertEmpty($error, "Can not execute '$cmd' command:\n$error");
    }

    /**
     * @param string $cmd
     */
    protected function startProcess($cmd)
    {
        $process = new Process('exec ' . $this->prepareCmd($cmd));
        $process->start();
        $this->processes[] = $process;
    }

    /**
     * @return TestJob
     */
    protected function createJob()
    {
        $job = new TestJob();
        $job->uid = uniqid();
        return $job;
    }

    /**
     * @param TestJob $job
     */
    protected function assertJobDone(TestJob $job)
    {
        $delay = 3000000;
        while (!file_exists($job->getFileName()) && $delay > 0) {
            usleep(50000);
            $delay -= 50000;
        }
        $this->assertFileExists($job->getFileName());
    }

    /**
     * @param TestJob $job
     * @param int $time
     */
    protected function assertJobLaterDone(TestJob $job, $time)
    {
        $delay = 3000000;
        while (!file_exists($job->getFileName()) && $delay > 0) {
            usleep(50000);
            $delay -= 50000;
        }
        $this->assertFileExists($job->getFileName());
        $this->assertGreaterThanOrEqual($time, filemtime($job->getFileName()));
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        // Kills started processes
        foreach ($this->processes as $process) {
            $process->stop();
        }
        $this->processes = [];

        // Removes temp job files
        foreach (glob(Yii::getAlias("@runtime/job-*.lock")) as $fileName) {
            unlink($fileName);
        }

        parent::tearDown();
    }
}