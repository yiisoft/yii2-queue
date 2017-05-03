<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace tests\drivers;

use Yii;
use tests\app\TestJob;
use zhuravljov\yii\queue\Queue;

/**
 * Driver Test Case
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class TestCase extends \tests\TestCase
{
    /**
     * @return Queue
     */
    abstract protected function getQueue();

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
        $delay = 5000000; // 5 sec
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
        $delay = 5000000; // 5 sec
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
        // Removes temp job files
        foreach (glob(Yii::getAlias("@runtime/job-*.lock")) as $fileName) {
            unlink($fileName);
        }

        parent::tearDown();
    }
}