<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tests\drivers\db;

use tests\app\PriorityJob;
use tests\drivers\CliTestCase;
use yii\db\Query;

/**
 * Db Queue Test Case
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class TestCase extends CliTestCase
{
    public function testPriority()
    {
        $this->getQueue()->priority(100)->push(new PriorityJob(['number' => 1]));
        $this->getQueue()->priority(300)->push(new PriorityJob(['number' => 5]));
        $this->getQueue()->priority(200)->push(new PriorityJob(['number' => 3]));
        $this->getQueue()->priority(200)->push(new PriorityJob(['number' => 4]));
        $this->getQueue()->priority(100)->push(new PriorityJob(['number' => 2]));
        $this->runProcess('php tests/yii queue/run');
        $this->assertEquals('12345', file_get_contents(PriorityJob::getFileName()));
    }

    public function testClear()
    {
        $this->getQueue()->push($this->createSimpleJob());
        $this->runProcess('php tests/yii queue/clear --interactive=0');
        $actual = (new Query())
            ->from($this->getQueue()->tableName)
            ->where(['channel' => $this->getQueue()->channel])
            ->count('*', $this->getQueue()->db);
        $this->assertEquals(0, $actual);
    }

    public function testRemove()
    {
        $id = $this->getQueue()->push($this->createSimpleJob());
        $this->runProcess("php tests/yii queue/remove $id");
        $actual = (new Query())
            ->from($this->getQueue()->tableName)
            ->where(['channel' => $this->getQueue()->channel, 'id' => $id])
            ->count('*', $this->getQueue()->db);
        $this->assertEquals(0, $actual);
    }

    protected function tearDown()
    {
        $this->getQueue()->db->createCommand()
            ->delete($this->getQueue()->tableName)
            ->execute();

        parent::tearDown();
    }
}