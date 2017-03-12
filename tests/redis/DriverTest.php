<?php

namespace tests\redis;

use Yii;
use tests\Process;
use tests\DriverTestCase;
use yii\base\NotSupportedException;

/**
 * Redis Driver Test
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class DriverTest extends DriverTestCase
{
    public function setUp()
    {
        parent::setUp();
        Yii::$app->redis->executeCommand('FLUSHDB');
    }

    public function testRun()
    {
        $job = $this->createJob();
        Yii::$app->redisQueue->push($job);
        Process::run('php tests/app/yii.php redis-queue/run');
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

    public function testLater()
    {
        $this->expectException(NotSupportedException::class);
        Yii::$app->amqpQueue->later($this->createJob(), 2);
    }
}