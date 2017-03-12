<?php

namespace tests\beanstalk;

use Yii;
use tests\Process;
use tests\DriverTestCase;

/**
 * Beanstalk Driver Test
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class DriverTest extends DriverTestCase
{
    public function testRun()
    {
        $job = $this->createJob();
        Yii::$app->beanstalkQueue->push($job);
        Process::run('php tests/app/yii.php beanstalk-queue/run');
        $this->assertJobDone($job);
    }

    public function testListen()
    {
        $pid = Process::start('php tests/app/yii.php beanstalk-queue/listen');
        $job = $this->createJob();
        Yii::$app->beanstalkQueue->push($job);
        $this->assertJobDone($job);
        Process::stop($pid);
    }

    public function testLater()
    {
        $pid = Process::start('php tests/app/yii.php beanstalk-queue/listen');
        $job = $this->createJob();
        Yii::$app->beanstalkQueue->later($job, 2);
        sleep(2);
        $this->assertJobLaterDone($job, time());
        Process::stop($pid);
    }
}