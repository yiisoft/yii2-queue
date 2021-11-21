<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tests\drivers\amqp_interop;

use Enqueue\AmqpLib\AmqpConnectionFactory;
use Enqueue\AmqpLib\AmqpConsumer;
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

        $queue = $this->getQueue();
        $queue->routingKey = $uniqKey;
        $queue->push($this->createSimpleJob());

        $factory = new AmqpConnectionFactory([
            'host' => $queue->host,
        ]);
        $context = $factory->createContext();

        $queue1 = $context->createQueue($queue->queueName);
        $queue1->addFlag(AmqpQueue::FLAG_DURABLE);
        $queue1->setArguments(['x-max-priority' => 10]);
        $context->declareQueue($queue1);
        $topic = $context->createTopic($queue->exchangeName);
        $topic->setType($queue->exchangeType);
        $topic->addFlag(AmqpTopic::FLAG_DURABLE);
        $context->declareTopic($topic);
        $context->bind(new AmqpBind($queue1, $topic, $queue->routingKey));

        $queue2 = $context->createQueue($queue->queueName);
        $consumer = $context->createConsumer($queue2);
        $callback = function (AmqpMessage $message) use (&$receivedRoutingKey) {
            $receivedRoutingKey = $message->getRoutingKey();
            return true;
        };
        $subscriptionConsumer = $context->createSubscriptionConsumer();
        $subscriptionConsumer->subscribe($consumer, $callback);
        $subscriptionConsumer->consume(1000);

        $this->assertSame($queue->routingKey, $receivedRoutingKey);
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
            var_dump($exitCode, $process->getExitCode());
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
}
