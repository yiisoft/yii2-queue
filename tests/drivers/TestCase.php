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
     * @param string|null $id of a job message
     */
    protected function assertJobDone(TestJob $job, $id)
    {
        $delay = 5000000; // 5 sec
        while (!file_exists($job->getFileName($id)) && $delay > 0) {
            usleep(50000);
            $delay -= 50000;
        }
        $this->assertFileExists($job->getFileName($id));
    }

    /**
     * @param TestJob $job
     * @param string|null $id of a job message
     * @param int $time
     */
    protected function assertJobLaterDone(TestJob $job, $id, $time)
    {
        $delay = 5000000; // 5 sec
        while (!file_exists($job->getFileName($id)) && $delay > 0) {
            usleep(50000);
            $delay -= 50000;
        }
        $this->assertFileExists($job->getFileName($id));
        $this->assertGreaterThanOrEqual($time, filemtime($job->getFileName($id)));
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