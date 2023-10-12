<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace tests\drivers\sqs;

use tests\app\RetryJob;
use tests\drivers\CliTestCase;
use Yii;
use yii\queue\sqs\Queue;

/**
 * SQS FIFO Queue Test.
 */
class FifoQueueTest extends CliTestCase
{
    public function testRun(): void
    {
        $job = $this->createSimpleJob();
        $this->getQueue()->push($job);
        $this->runProcess(['php', 'yii', 'queue/run']);

        $this->assertSimpleJobDone($job);
    }

    public function testListen(): void
    {
        $this->startProcess(['php', 'yii', 'queue/listen', '1']);
        $job = $this->createSimpleJob();
        $this->getQueue()->push($job);

        $this->assertSimpleJobDone($job);
    }

    public function testFifoQueueDoesNotSupportPerMessageDelays(): void
    {
        $this->startProcess(['php', 'yii', 'queue/listen', '1']);
        $job = $this->createSimpleJob();

        $this->setExpectedException('\Aws\Sqs\Exception\SqsException');
        $this->getQueue()->delay(2)->push($job);
    }

    public function testRetry(): void
    {
        $this->startProcess(['php', 'yii', 'queue/listen', '1']);
        $job = new RetryJob(['uid' => uniqid()]);
        $this->getQueue()->push($job);
        sleep(6);

        $this->assertFileExists($job->getFileName());
        $this->assertEquals('aa', file_get_contents($job->getFileName()));
    }

    public function testClear(): void
    {
        if (!getenv('AWS_SQS_FIFO_CLEAR_TEST_ENABLED')) {
            $this->markTestSkipped(__METHOD__ . ' is disabled');
        }

        $this->getQueue()->push($this->createSimpleJob());
        $this->runProcess(['php', 'yii', 'queue/clear', '--interactive=0']);
    }

    /**
     * @return Queue
     */
    protected function getQueue(): Queue
    {
        return Yii::$app->sqsFifoQueue;
    }

    protected function setUp(): void
    {
        if (!getenv('AWS_SQS_FIFO_ENABLED')) {
            $this->markTestSkipped('AWS SQS FIFO tests are disabled');
        }

        parent::setUp();
    }
}
