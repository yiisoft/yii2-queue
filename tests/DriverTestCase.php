<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace tests;

use Yii;
use tests\app\TestJob;

/**
 * Class DriverTestCase
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class DriverTestCase extends TestCase
{
    /**
     * @var int[] ids of started processes
     */
    private $pids = [];

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        parent::tearDown();
        // Kills started processes
        foreach ($this->pids as $pid) {
            exec("kill $pid");
        }
        // Removes temp job files
        foreach (glob(Yii::getAlias("@runtime/job-*.lock")) as $fileName) {
            unlink($fileName);
        }
    }

    /**
     * @param string $cmd
     */
    protected function runProcess($cmd)
    {
        exec($cmd);
    }

    /**
     * @param string $cmd
     */
    protected function startProcess($cmd)
    {
        $this->pids[] = (int) exec(strtr('nohup {cmd} >/dev/null 2>&1 & echo $!', [
            '{cmd}' => $cmd,
        ]));
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
}