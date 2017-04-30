<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace tests\drivers\sync;

use Yii;
use tests\drivers\TestCase;
use zhuravljov\yii\queue\sync\Queue;

/**
 * Sync Queue Test
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
        return Yii::$app->syncQueue;
    }

    public function testRun()
    {
        $job = $this->createJob();
        $id = $this->getQueue()->push($job);
        $this->getQueue()->run();
        $this->assertJobDone($job, $id);
    }

    public function testStatus()
    {
        $job = $this->createJob();
        $id = $this->getQueue()->push($job);
        $isWaiting = $this->getQueue()->isWaiting($id);
        $isStarted = false;
        $beforeExec = function () use ($id, &$isStarted) {
            $isStarted = $this->getQueue()->isStarted($id);
        };
        $this->getQueue()->on(Queue::EVENT_BEFORE_EXEC, $beforeExec);
        $this->getQueue()->run();
        $this->getQueue()->off(Queue::EVENT_BEFORE_EXEC, $beforeExec);
        $isFinished = $this->getQueue()->isFinished($id);

        $this->assertTrue($isWaiting);
        $this->assertTrue($isStarted);
        $this->assertTrue($isFinished);
    }
}