<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace tests\closure;

use tests\TestCase;
use Yii;
use yii\queue\closure\Behavior;
use yii\queue\sync\Queue;

/**
 * Closure Test.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class ClosureTest extends TestCase
{
    public function testPush1()
    {
        $this->getQueue()->push(function () {
            $fileName = Yii::getAlias('@runtime/job-1.lock');
            file_put_contents($fileName, '');
        });
        $this->getQueue()->run();
        $this->assertFileExists(Yii::getAlias('@runtime/job-1.lock'));
    }

    public function testPush2()
    {
        $fileName = Yii::getAlias('@runtime/job-2.lock');
        $this->getQueue()->push(function () use ($fileName) {
            file_put_contents($fileName, '');
        });
        $this->getQueue()->run();
        $this->assertFileExists($fileName);
    }

    public function testPush3()
    {
        $job = new ClosureJob([
            'closure' => function () {
                $fileName = Yii::getAlias('@runtime/job-3.lock');
                file_put_contents($fileName, '');
            }
        ]);
        $this->getQueue()->push($job);
        $this->getQueue()->run();
        $this->assertFileExists(Yii::getAlias('@runtime/job-3.lock'));
    }

    public function testPush4()
    {
        $fileName = Yii::getAlias('@runtime/job-4.lock');
        $job = new ClosureJob([
            'closure' => function () use ($fileName) {
                file_put_contents($fileName, '');
            }
        ]);
        $this->getQueue()->push($job);
        $this->getQueue()->run();
        $this->assertFileExists($fileName);
    }

    /**
     * @return Queue
     */
    protected function getQueue()
    {
        if (!$this->_queue) {
            $this->_queue = new Queue([
                'handle' => false,
                'as closure' => Behavior::class,
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
