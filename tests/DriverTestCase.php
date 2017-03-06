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
        $job->uid = uniqid();
        return $job;
    }

    /**
     * @param TestJob $job
     */
    protected function assertJobDone(TestJob $job)
    {
        $delay = 3000000;
        while (!$job->getFilesCount() && $delay > 0) {
            usleep(50000);
            $delay -= 50000;
        }
        $this->assertEquals(1, $job->getFilesCount());
    }

}