<?php

namespace tests\drivers\queue_interop;

use tests\app\PriorityJob;
use tests\drivers\CliTestCase;
use yii\queue\queue_interop\Queue;

class QueueTest extends CliTestCase
{
    protected function setUp()
    {
        if ('true' == getenv('EXCLUDE_ENQUEUE')) {
            $this->markTestSkipped('Queue interop tests are disabled for php 5.5');
        }

        parent::setUp();
    }

    /**
     * @return Queue
     */
    protected function getQueue()
    {
        $queue = \Yii::$app->interopQueue;
        $queue->setupBroker();

        return $queue;
    }

    public function testPriority()
    {
        $this->getQueue()->priority(0)->push(new PriorityJob(['number' => 1]));
        $this->getQueue()->priority(4)->push(new PriorityJob(['number' => 5]));
        $this->getQueue()->priority(2)->push(new PriorityJob(['number' => 3]));
        $this->getQueue()->priority(3)->push(new PriorityJob(['number' => 4]));
        $this->getQueue()->priority(1)->push(new PriorityJob(['number' => 2]));
        $this->runProcess('php tests/yii queue/run');
        $this->assertEquals('12345', file_get_contents(PriorityJob::getFileName()));
    }

    public function testStatus()
    {
        // Not supported
    }
}
