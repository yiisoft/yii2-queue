<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace tests\drivers\gearman;

use Yii;
use tests\drivers\TestCase;
use zhuravljov\yii\queue\drivers\gearman\Queue;

/**
 * Gearman Queue Test
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class QueueTest extends TestCase
{
    /**
     * @return Queue
     */
    protected function getQueue()
    {
        return Yii::$app->gearmanQueue;
    }

    public function testRun()
    {
        $job = $this->createJob();
        $id = $this->getQueue()->push($job);
        $this->runProcess('php tests/yii queue/run');
        $this->assertJobDone($job, $id);
    }

    public function testListen()
    {
        $this->startProcess('php tests/yii queue/listen');
        $job = $this->createJob();
        $id = $this->getQueue()->push($job);
        $this->assertJobDone($job, $id);
    }

    public function setUp()
    {
        if (!defined('GEARMAN_SUCCESS')) {
            $this->markTestSkipped();
        } else {
            parent::setUp();
        }
    }
}