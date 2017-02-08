<?php

namespace tests\redis;

use Yii;
use tests\Process;
use tests\DriverTestCase;

/**
 * Redis Driver Test
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class DriverTest extends DriverTestCase
{
    public function testRun()
    {
        $job = $this->createJob();
        Yii::$app->redisQueue->push($job);
        Process::start('php tests/app/yii.php redis-queue/run');
        $this->assertJobDone($job);
    }

    public function testListen()
    {
        $pid = Process::start('php tests/app/yii.php redis-queue/listen');
        $job = $this->createJob();
        Yii::$app->redisQueue->push($job);
        $this->assertJobDone($job);
        Process::stop($pid);
    }
}