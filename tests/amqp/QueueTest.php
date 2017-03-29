<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace tests\amqp;

use Yii;
use tests\QueueTestCase;
use zhuravljov\yii\queue\amqp\Queue;

/**
 * AMQP Queue Test
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class QueueTest extends QueueTestCase
{
    /**
     * @return Queue
     */
    protected function getQueue()
    {
        return Yii::$app->amqpQueue;
    }

    public function testListen()
    {
        $this->startProcess('php tests/yii queue/listen');
        $job = $this->createJob();
        $this->getQueue()->push($job);
        $this->assertJobDone($job);
    }
}