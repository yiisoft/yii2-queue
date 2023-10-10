<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace tests\drivers\amqp_interop;

use Interop\Amqp\AmqpConsumer;
use Interop\Amqp\AmqpMessage;
use Interop\Amqp\Impl\AmqpMessage as InteropAmqpMessage;
use tests\app\PriorityJob;
use tests\app\RetryJob;
use Yii;
use yii\queue\amqp_interop\Queue;

/**
 * AMQP Queue Test.
 *
 * @author Maksym Kotliar <kotlyar.maksim@gmail.com>
 */
class QueueTest extends AmqpTestCase
{
    /**
     * Test working setter routing key
     */
    public function testNativeSettingRoutingKey(): void
    {
        $this->exchangeName = null;
        $this->queueName = null;

        $uniqRoutingKey = Yii::$app->security->generateRandomString(12);
        $message = new InteropAmqpMessage();
        $message->setRoutingKey($uniqRoutingKey);

        $this->assertSame($uniqRoutingKey, $message->getRoutingKey());
    }

    public function testListen(): void
    {
        $this->startProcess(['php', 'yii', 'queue/listen']);
        $job = $this->createSimpleJob();
        $this->getQueue()->push($job);

        $this->assertSimpleJobDone($job);
    }

    public function testLater(): void
    {
        $this->startProcess(['php', 'yii', 'queue/listen']);
        $job = $this->createSimpleJob();
        $this->getQueue()->delay(2)->push($job);

        $this->assertSimpleJobLaterDone($job, 2);
    }

    public function testRetry(): void
    {
        $this->startProcess(['php', 'yii', 'queue/listen']);
        $job = new RetryJob(['uid' => uniqid('', true)]);
        $this->getQueue()->push($job);
        sleep(6);

        $this->assertFileExists($job->getFileName());
        $this->assertEquals('aa', file_get_contents($job->getFileName()));
    }

    public function testPriority(): void
    {
        $this->getQueue()->priority(3)->push(new PriorityJob(['number' => 1]));
        $this->getQueue()->priority(1)->push(new PriorityJob(['number' => 5]));
        $this->getQueue()->priority(2)->push(new PriorityJob(['number' => 3]));
        $this->getQueue()->priority(2)->push(new PriorityJob(['number' => 4]));
        $this->getQueue()->priority(3)->push(new PriorityJob(['number' => 2]));
        $this->startProcess(['php', 'yii', 'queue/listen']);
        sleep(3);

        $this->assertEquals('12345', file_get_contents(PriorityJob::getFileName()));
    }

    /**
     * @requires extension pcntl
     */
    public function testSignals(): void
    {
        $signals = [
            1 => 129, // SIGHUP
            2 => 130, // SIGINT
            3 => 131, // SIGQUIT
            15 => 143, // SIGTERM
        ];

        foreach ($signals as $signal => $exitCode) {
            $process = $this->startProcess(['php', 'yii', 'queue/listen']);
            $this->assertTrue($process->isRunning());
            $process->signal($signal);
            $process->wait();
            $this->assertFalse($process->isRunning());
            $this->assertEquals($exitCode, $process->getExitCode());
        }
    }

    /**
     * Sending a message to a queue using RoutingKey
     */
    public function testSendMessageWithRoutingKey(): void
    {
        $this->queueName = 'routing-key';
        $this->exchangeName = 'routing-key';
        $this->routingKey = Yii::$app->security->generateRandomString(10);

        $receivedRoutingKey = null;

        $queue = $this->getQueue(true);
        $queue->exchangeName = $this->exchangeName;
        $queue->queueName = $this->queueName;
        $queue->routingKey = $this->routingKey;
        $queue->push($this->createSimpleJob());

        $context = $this->getAMQPContext();
        $consumer = $context->createConsumer(
            $context->createQueue($this->queueName)
        );
        $callback = function (AmqpMessage $message, AmqpConsumer $consumer) use (&$receivedRoutingKey) {
            $receivedRoutingKey = $message->getRoutingKey();
            $consumer->acknowledge($message);
            return true;
        };
        $subscriptionConsumer = $context->createSubscriptionConsumer();
        $subscriptionConsumer->subscribe($consumer, $callback);
        $subscriptionConsumer->consume(1000);

        $this->assertSame($this->routingKey, $receivedRoutingKey);
    }

    /**
     * Test push message with headers
     * @return void
     */
    public function testPushMessageWithHeaders(): void
    {
        $this->queueName = 'message-headers';
        $this->exchangeName = 'message-headers';

        $actualHeaders = [];
        $messageHeaders = [
            'header-1' => 'header-value-1',
            'header-2' => 'header-value-2',
        ];

        $queue = $this->getQueue(true);
        $queue->exchangeName = $this->exchangeName;
        $queue->queueName = $this->queueName;
        $queue->setMessageHeaders = $messageHeaders;
        $queue->push($this->createSimpleJob());

        $context = $this->getAMQPContext();
        $consumer = $context->createConsumer(
            $context->createQueue($this->queueName)
        );
        $callback = function (AmqpMessage $message, AmqpConsumer $consumer) use (&$actualHeaders) {
            /**
             * This not mistake. In original package this function mixed up
             * getHeaders() => getProperties()
             */
            $actualHeaders = $message->getProperties();
            $consumer->acknowledge($message);
            return true;
        };
        $subscriptionConsumer = $context->createSubscriptionConsumer();
        $subscriptionConsumer->subscribe($consumer, $callback);
        $subscriptionConsumer->consume(1000);


        $expectedHeaders = array_merge(
            $messageHeaders,
            [
                Queue::ATTEMPT => 1,
                Queue::TTR => 300,
            ]
        );
        $this->assertEquals($expectedHeaders, $actualHeaders);
    }
}
