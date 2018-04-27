<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tests\drivers\sqs;

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
        $job = $this->createSimpleJob();
        $this->getQueue()->push($job);
        $this->runProcess('php yii queue/run');

        $this->assertSimpleJobDone($job);
    }

    public function testListen()
    {
        $this->startProcess('php yii queue/listen 1');
        $job = $this->createSimpleJob();
        $this->getQueue()->push($job);

        $this->assertSimpleJobDone($job);
    }

    public function testLater()
    {
        $this->startProcess('php yii queue/listen 1');
        $job = $this->createSimpleJob();
        $this->getQueue()->delay(2)->push($job);

        $this->assertSimpleJobLaterDone($job, 2);
    }

    public function testClear()
    {
        $this->getQueue()->push($this->createSimpleJob());
        $this->runProcess('php yii queue/clear --interactive=0');
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
        if (getenv('AWS_SQS_URL') === false) {
            $this->markTestSkipped('AWS SQS tests are disabled');
        }

        parent::setUp();
    }
}