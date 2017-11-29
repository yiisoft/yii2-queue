<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tests\drivers\amqp_interop;

use tests\drivers\CliTestCase;
use Yii;
use yii\queue\amqp_interop\Queue;

/**
 * AMQP Queue Test
 *
 * @author Maksym Kotliar <kotlyar.maksim@gmail.com>
 */
class QueueTest extends CliTestCase
{
    protected function setUp()
    {
        if ('true' == getenv('EXCLUDE_AMQP_INTEROP')) {
            $this->markTestSkipped('Amqp tests are disabled for php 5.5');
        }

        /** @var Queue $queue */
        $queue = Yii::$app->amqpInteropQueue;
        $queue->getContext()->deleteQueue($queue->getContext()->createQueue($queue->queueName));
        $queue->getContext()->deleteTopic($queue->getContext()->createTopic($queue->exchangeName));

        parent::setUp();
    }

    /**
     * @return Queue
     */
    protected function getQueue()
    {
        return Yii::$app->amqpInteropQueue;
    }

    public function testRun()
    {
        $this->markTestSkipped('Not supported');
    }

    public function testStatus()
    {
        $this->markTestSkipped('Not supported');
    }

    public function testLater()
    {
        $this->markTestSkipped('Not supported');
    }

    public function testRetry()
    {
        $this->markTestSkipped('Not supported');
    }
}