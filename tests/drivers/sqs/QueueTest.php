<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tests\drivers\sqs;

use tests\app\RetryJob;
use tests\drivers\CliTestCase;
use Yii;
use yii\queue\sqs\Queue;

/**
 * SQS Queue Test
 */
class QueueTest extends CliTestCase
{
    public function testRun()
    {
        $client = $this->getMockBuilder('Aws\Sqs\SqsClient')
            ->disableOriginalConstructor()
            ->setMethods([
                'receiveMessage',
                'sendMessage',
                'changeMessageVisibility',
                'deleteMessage',
            ])
            ->getMock();

        $client->method('sendMessage')
            ->willReturn(['MessageId' => '1']);

        $client->method('deleteMessage')
            ->willReturn(true);

        $job = $this->createSimpleJob();
        $serialized = serialize($job);

        $client->method('receiveMessage')
            ->will($this->onConsecutiveCalls(
                ['Messages' => [[
                    'MessageId' => '1',
                    'ReceiptHandle' => '1',
                    'MD5OfBody' => '1',
                    'Body' => '300;'.$serialized,
                ]]],
                ['Messages' => []]));


        $this->getQueue()->setClient($client);

        $this->getQueue()->push($job);
        $this->getQueue()->run(false);
        $this->assertSimpleJobDone($job);
    }

    /**
     * @return Queue
     */
    protected function getQueue()
    {
        return Yii::$app->sqsQueue;
    }
}