<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace tests\drivers\amqp;

use tests\drivers\CliTestCase;
use Yii;
use zhuravljov\yii\queue\amqp\Queue;

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
}