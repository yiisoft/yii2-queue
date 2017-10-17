<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tests\stoppable;

use tests\app\SimpleJob;
use tests\TestCase;
use Yii;
use yii\caching\ArrayCache;
use yii\queue\stoppable\Behavior as Stoppable;
use yii\queue\sync\Queue as SyncQueue;

/**
 * Stoppable Test
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class StoppableTest extends TestCase
{
    public function testStop()
    {
        $job = new SimpleJob(['uid' => uniqid()]);
        $id = $this->getQueue()->push($job);
        $this->getQueue()->stop($id);
        $this->getQueue()->run();
        $this->assertFileNotExists($job->getFileName());
    }

    public function testNotStop()
    {
        $job = new SimpleJob(['uid' => uniqid()]);
        $this->getQueue()->push($job);
        $this->getQueue()->run();
        $this->assertFileExists($job->getFileName());
    }

    /**
     * @return SyncQueue|Stoppable
     */
    protected function getQueue()
    {
        if (!$this->_queue) {
            $this->_queue = new SyncQueue([
                'handle' => false,
                'as stoppable' => [
                    'class' => Stoppable::class,
                    'cache' => ArrayCache::class,
                ],
            ]);
        }
        return $this->_queue;
    }

    private $_queue;

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        foreach (glob(Yii::getAlias("@runtime/job-*.lock")) as $fileName) {
            unlink($fileName);
        }
        parent::tearDown();
    }
}