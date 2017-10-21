<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tests\drivers\file;

use tests\drivers\CliTestCase;
use Yii;
use yii\helpers\FileHelper;
use yii\queue\file\Queue;

/**
 * File Queue Test
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class QueueTest extends CliTestCase
{
    public function testClear()
    {
        $this->getQueue()->push($this->createSimpleJob());
        $this->assertNotEmpty(glob($this->getQueue()->path . '/job*.data'));
        $this->runProcess('php tests/yii queue/clear --interactive=0');
        $this->assertEmpty(glob($this->getQueue()->path . '/job*.data'));
    }

    public function testRemove()
    {
        $id = $this->getQueue()->push($this->createSimpleJob());
        $this->assertFileExists($this->getQueue()->path . "/job$id.data");
        $this->runProcess("php tests/yii queue/remove $id");
        $this->assertFileNotExists($this->getQueue()->path . "/job$id.data");
    }

    /**
     * @return Queue
     */
    protected function getQueue()
    {
        return Yii::$app->fileQueue;
    }

    protected function tearDown()
    {
        foreach (glob(Yii::getAlias("@runtime/queue/*")) as $fileName) {
            unlink($fileName);
        }
        parent::tearDown();
    }
}