<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace tests\drivers\redis;

use Yii;
use tests\drivers\TestCase;
use zhuravljov\yii\queue\drivers\redis\Queue;

/**
 * Redis Queue Test
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class QueueTest extends TestCase
{
    /**
     * @return Queue
     */
    protected function getQueue()
    {
        return Yii::$app->redisQueue;
    }

    public function testRun()
    {
        $job = $this->createJob();
        $id = $this->getQueue()->push($job);
        $this->runProcess('php tests/yii queue/run');
        $this->assertJobDone($job, $id);
    }

    public function testStatus()
    {
        $job = $this->createJob();
        $id = $this->getQueue()->push($job);
        $this->assertTrue($this->getQueue()->isWaiting($id));
        $this->runProcess('php tests/yii queue/run');
        $this->assertTrue($this->getQueue()->isFinished($id));
    }

    public function testListen()
    {
        $this->startProcess('php tests/yii queue/listen');
        $job = $this->createJob();
        $id = $this->getQueue()->push($job);
        $this->assertJobDone($job, $id);
    }

    public function testLater()
    {
        $this->startProcess('php tests/yii queue/listen');
        $job = $this->createJob();
        $id = $this->getQueue()->later($job, 2);
        sleep(2);
        $this->assertJobLaterDone($job, $id, time());
    }

    protected function tearDown()
    {
        $this->getQueue()->redis->executeCommand('FLUSHDB');
        parent::tearDown();
    }
}