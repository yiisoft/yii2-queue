<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tests\drivers\amqp;

use tests\drivers\CliTestCase;
use Yii;
use yii\queue\amqp\Queue;

/**
 * AMQP Queue Test
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class QueueTest extends CliTestCase
{
    /**
     * @return Queue
     */
    protected function getQueue()
    {
        return Yii::$app->amqpQueue;
    }

    public function testRun()
    {
        // Not supported
    }

    public function testStatus()
    {
        // Not supported
    }

    public function testLater()
    {
        // Not supported
    }

    public function testRetry()
    {
        // Limited support
    }
}