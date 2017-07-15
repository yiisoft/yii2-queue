<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tests\drivers\redis;

use tests\drivers\CliTestCase;
use Yii;
use yii\queue\redis\Queue;

/**
 * Redis Queue Test
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
        return Yii::$app->redisQueue;
    }

    protected function tearDown()
    {
        $this->getQueue()->redis->executeCommand('FLUSHDB');

        parent::tearDown();
    }
}