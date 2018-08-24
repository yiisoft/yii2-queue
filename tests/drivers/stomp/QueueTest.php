<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tests\drivers\stomp;

use tests\app\PriorityJob;
use tests\app\RetryJob;
use tests\drivers\CliTestCase;
use Yii;
use yii\queue\stomp\Queue;


class QueueTest extends CliTestCase
{
    public function testListen()
    {
        $this->startProcess('php yii queue/listen');
        $job = $this->createSimpleJob();
        $this->getQueue()->push($job);

        $this->assertSimpleJobDone($job);
    }

    public function testLater()
    {
        $this->startProcess('php yii queue/listen');
        $job = $this->createSimpleJob();
        $this->getQueue()->delay(2)->push($job);

        $this->assertSimpleJobLaterDone($job, 2);
    }

    public function testRetry()
    {
        $this->startProcess('php yii queue/listen');
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
        $this->startProcess('php yii queue/listen');
        sleep(3);

        $this->assertEquals('12345', file_get_contents(PriorityJob::getFileName()));
    }

    /**
     * @return Queue
     */
    protected function getQueue()
    {
        return Yii::$app->stompQueue;
    }

}
