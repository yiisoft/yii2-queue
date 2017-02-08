<?php

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
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();
        foreach (glob(Yii::getAlias("@runtime/job-*.lock")) as $fileName) {
            unlink($fileName);
        }
    }

    /**
     * @return TestJob
     */
    protected function createJob()
    {
        $job = new TestJob();
        $uid = uniqid();
        $job->fileName = Yii::getAlias("@runtime/job-{$uid}.lock");
        return $job;
    }

    /**
     * @param TestJob $job
     * @param string $massage
     */
    protected function assertJobDone(TestJob $job, $massage = '')
    {
        $delay = 3000000;
        while (!file_exists($job->fileName) && $delay > 0) {
            usleep(50000);
            $delay -= 50000;
        }
        $this->assertFileExists($job->fileName, $massage);
    }

}