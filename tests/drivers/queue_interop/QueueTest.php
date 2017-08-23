<?php

namespace tests\drivers\queue_interop;

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

    public function testRun()
    {
        // Not supported
    }

    public function testStatus()
    {
        // Not supported
    }

    public function testRetry()
    {
        // Limited support
    }
}
