<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace tests\drivers\amqp_interop;

use Enqueue\AmqpLib\AmqpConnectionFactory;
use Interop\Amqp\AmqpMessage;
use Interop\Amqp\Impl\AmqpMessage as InteropAmqpMessage;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\Impl\AmqpBind;
use Interop\Amqp\AmqpQueue;
use tests\app\PriorityJob;
use tests\app\RetryJob;
use tests\drivers\CliTestCase;
use Yii;
use yii\queue\amqp_interop\Queue;

/**
 * AMQP Queue Test.
 *
 * @author Maksym Kotliar <kotlyar.maksim@gmail.com>
 */
class QueueTest extends CliTestCase
{
    /**
     * Test working setter routing key
     */
    public function testNativeSettingRoutingKey()
    {
        $uniqRoutingKey = Yii::$app->security->generateRandomString(12);
        $message = new InteropAmqpMessage();
        $message->setRoutingKey($uniqRoutingKey);

        $this->assertSame($uniqRoutingKey, $message->getRoutingKey());
    }

    /**
     * Sending a message to a queue using RoutingKey
     */
    public function testSendMessageWithRoutingKey()
    {
        $uniqKey = Yii::$app->security->generateRandomString(12);
        $receivedRoutingKey = null;

        $yiiQueue = $this->getQueue();
        $yiiQueue->routingKey = $uniqKey;
        $yiiQueue->push($this->createSimpleJob());

        $context = $this->getNativeAMQPContext($yiiQueue);

        $queue = $context->createQueue($yiiQueue->queueName);
        $consumer = $context->createConsumer($queue);
        $callback = function (AmqpMessage $message) use (&$receivedRoutingKey) {
            $receivedRoutingKey = $message->getRoutingKey();
            return true;
        };
        $subscriptionConsumer = $context->createSubscriptionConsumer();
        $subscriptionConsumer->subscribe($consumer, $callback);
        $subscriptionConsumer->consume(1000);

        sleep(3);

        $this->assertSame($yiiQueue->routingKey, $receivedRoutingKey);
    }

    /**
     * Test push message with headers
     * @return void
     */
    public function testPushMessageWithHeaders()
    {
        $actualHeaders = [];
        $messageHeaders = [
            'header-1' => 'header-value-1',
            'header-2' => 'header-value-2',
        ];

        $yiiQueue = $this->getQueue();
        $yiiQueue->setMessageHeaders = $messageHeaders;
        $yiiQueue->push($this->createSimpleJob());

        $context = $this->getNativeAMQPContext($yiiQueue);

        $queue = $context->createQueue($yiiQueue->queueName);
        $consumer = $context->createConsumer($queue);
        $callback = function (AmqpMessage $message) use (&$actualHeaders) {
            /**
             * This not mistake. In original package this function mixed up
             * getHeaders() => getProperties()
             */
            $actualHeaders = $message->getProperties();
            return true;
        };
        $subscriptionConsumer = $context->createSubscriptionConsumer();
        $subscriptionConsumer->subscribe($consumer, $callback);
        $subscriptionConsumer->consume(1000);

        sleep(3);

        $expectedHeaders = array_merge(
            $messageHeaders,
            [
                Queue::ATTEMPT => 1,
                Queue::TTR => 300,
            ]
        );
        $this->assertEquals($expectedHeaders, $actualHeaders);
    }

    public function testListen()
    {
        $this->startProcess(['php', 'yii', 'queue/listen']);
        $job = $this->createSimpleJob();
        $this->getQueue()->push($job);

        $this->assertSimpleJobDone($job);
    }

    public function testLater()
    {
        $this->startProcess(['php', 'yii', 'queue/listen']);
        $job = $this->createSimpleJob();
        $this->getQueue()->delay(2)->push($job);

        $this->assertSimpleJobLaterDone($job, 2);
    }

    public function testRetry()
    {
        $this->startProcess(['php', 'yii', 'queue/listen']);
        $job = new RetryJob(['uid' => uniqid()]);
        $this->getQueue()->push($job);
        sleep(6);

        $this->assertFileExists($job->getFileName());
        $this->assertEquals('aa', file_get_contents($job->getFileName()));
    }

    public function testPriority()
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
    public function testSignals()
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
     * @return Queue
     */
    protected function getQueue()
    {
        return Yii::$app->amqpInteropQueue;
    }

    protected function setUp()
    {
        if ('true' == getenv('EXCLUDE_AMQP_INTEROP')) {
            $this->markTestSkipped('Amqp tests are disabled for php 5.5');
        }

        parent::setUp();
    }

    /**
     * @param Queue $yiiQueue
     * @return mixed
     */
    private function getNativeAMQPContext($yiiQueue)
    {
        $factory = new AmqpConnectionFactory([
            'host' => $yiiQueue->host,
        ]);
        $context = $factory->createContext();

        $queue = $context->createQueue($yiiQueue->queueName);
        $queue->addFlag(AmqpQueue::FLAG_DURABLE);
        $queue->setArguments(['x-max-priority' => 10]);
        $context->declareQueue($queue);

        $topic = $context->createTopic($yiiQueue->exchangeName);
        $topic->setType($yiiQueue->exchangeType);
        $topic->addFlag(AmqpTopic::FLAG_DURABLE);
        $context->declareTopic($topic);

        $context->bind(new AmqpBind($queue, $topic, $yiiQueue->routingKey));

        return $context;
    }
}
