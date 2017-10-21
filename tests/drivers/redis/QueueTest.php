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
    public function testClear()
    {
        $this->getQueue()->push($this->createSimpleJob());
        $this->assertNotEmpty($this->getQueue()->redis->keys($this->getQueue()->channel . '.*'));
        $this->runProcess('php tests/yii queue/clear --interactive=0');
        $this->assertEmpty($this->getQueue()->redis->keys($this->getQueue()->channel . '.*'));
    }

    public function testRemove()
    {
        $id = $this->getQueue()->push($this->createSimpleJob());
        $this->assertTrue((bool) $this->getQueue()->redis->hexists($this->getQueue()->channel . '.messages', $id));
        $this->runProcess("php tests/yii queue/remove $id");
        $this->assertFalse((bool) $this->getQueue()->redis->hexists($this->getQueue()->channel . '.messages', $id));
    }

    /**
     * @return Queue
     */
    protected function getQueue()
    {
        return Yii::$app->redisQueue;
    }

    protected function tearDown()
    {
        $this->getQueue()->redis->flushdb();
        parent::tearDown();
    }
}