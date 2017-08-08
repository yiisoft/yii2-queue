<?php

namespace tests\drivers\queue_interop;

use tests\drivers\CliTestCase;
use yii\queue\queue_interop\Queue;

class QueueTest extends CliTestCase
{
    /**
     * @return Queue
     */
    protected function getQueue()
    {
        return \Yii::$app->interopQueue;
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
