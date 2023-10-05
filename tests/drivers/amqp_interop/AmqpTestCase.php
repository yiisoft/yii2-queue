<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace tests\drivers\amqp_interop;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use tests\drivers\CliTestCase;
use Yii;
use yii\queue\amqp_interop\Queue;

abstract class AmqpTestCase extends CliTestCase
{
    public bool $activeQueue = false;

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->purgeQueue();
    }

    /**
     * @return Queue
     */
    protected function getQueue(): Queue
    {
        $this->activeQueue = true;

        return Yii::$app->amqpInteropQueue;
    }

    private function purgeQueue(): void
    {
        if ($this->activeQueue) {
            $queue = $this->getQueue();

            $connection = new AMQPStreamConnection(
                $queue->host,
                $queue->port,
                $queue->user,
                $queue->password
            );
            $channel = $connection->channel();
            $channel->queue_bind($queue->queueName, $queue->exchangeName);
            $channel->queue_purge($queue->queueName);
            sleep(5);
        }
    }
}
