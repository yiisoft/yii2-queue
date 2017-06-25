<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace tests\drivers;

use Symfony\Component\Process\Process;
use tests\app\PriorityJob;
use Yii;

/**
 * Class CliTestCase
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class CliTestCase extends TestCase
{
    public function testRun()
    {
        $job = $this->createSimpleJob();
        $this->getQueue()->push($job);
        $this->runProcess('php tests/yii queue/run');
        $this->assertSimpleJobDone($job);
    }

    public function testStatus()
    {
        $job = $this->createSimpleJob();
        $id = $this->getQueue()->push($job);
        $this->assertTrue($this->getQueue()->isWaiting($id));
        $this->runProcess('php tests/yii queue/run');
        $this->assertTrue($this->getQueue()->isDone($id));
    }

    public function testListen()
    {
        $this->startProcess('php tests/yii queue/listen');
        $job = $this->createSimpleJob();
        $this->getQueue()->push($job);
        $this->assertSimpleJobDone($job);
    }

    public function testLater()
    {
        $this->startProcess('php tests/yii queue/listen');
        $job = $this->createSimpleJob();
        $this->getQueue()->delay(2)->push($job);
        $this->assertSimpleJobLaterDone($job, 2);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
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
     * @param string $cmd
     * @return string
     */
    private function prepareCmd($cmd)
    {
        $class = new \ReflectionClass($this->getQueue());
        $method = $class->getMethod('getCommandId');
        $method->setAccessible(true);

        return strtr($cmd, [
            'php' => PHP_BINARY,
            'queue' => $method->invoke($this->getQueue()),
        ]);
    }

    /**
     * @var Process[] ids of started processes
     */
    private $processes = [];

}