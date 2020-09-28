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
 * SQS Queue Test.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class QueueTest extends CliTestCase
{
    public function testRun()
    {
        $job = $this->createSimpleJob();
        $this->getQueue()->push($job);
        $this->runProcess(['php', 'yii', 'queue/run']);

        $this->assertSimpleJobDone($job);
    }

    public function testListen()
    {
        $this->startProcess(['php', 'yii', 'queue/listen', '1']);
        $job = $this->createSimpleJob();
        $this->getQueue()->push($job);

        $this->assertSimpleJobDone($job);
    }

    public function testLater()
    {
        $this->startProcess(['php', 'yii', 'queue/listen', '1']);
        $job = $this->createSimpleJob();
        $this->getQueue()->delay(2)->push($job);

        $this->assertSimpleJobLaterDone($job, 2);
    }

    public function testRetry()
    {
        $this->startProcess(['php', 'yii', 'queue/listen', '1']);
        $job = new RetryJob(['uid' => uniqid()]);
        $this->getQueue()->push($job);
        sleep(6);

        $this->assertFileExists($job->getFileName());
        $this->assertEquals('aa', file_get_contents($job->getFileName()));
    }

    public function testClear()
    {
        if (!getenv('AWS_SQS_CLEAR_TEST_ENABLED')) {
            $this->markTestSkipped(__METHOD__ . ' is disabled');
        }

        $this->getQueue()->push($this->createSimpleJob());
        $this->runProcess(['php', 'yii', 'queue/clear', '--interactive=0']);
    }

    /**
     * @return Queue
     */
    protected function getQueue()
    {
        return Yii::$app->sqsQueue;
    }

    protected function setUp()
    {
        if (!getenv('AWS_SQS_ENABLED')) {
            $this->markTestSkipped('AWS SQS tests are disabled');
        }

        parent::setUp();
    }
}
