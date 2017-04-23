<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace tests\drivers\file;

use Yii;
use tests\drivers\TestCase;
use zhuravljov\yii\queue\drivers\file\Queue;

/**
 * File Queue Test
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
        return Yii::$app->fileQueue;
    }

    public function testRun()
    {
        $job = $this->createJob();
        $this->getQueue()->push($job);
        $this->runProcess('php tests/yii queue/run');
        $this->assertJobDone($job);
    }

    public function testListen()
    {
        $this->startProcess('php tests/yii queue/listen');
        $job = $this->createJob();
        $this->getQueue()->push($job);
        $this->assertJobDone($job);
    }


    public function testLater()
    {
        $this->startProcess('php tests/yii queue/listen');
        $job = $this->createJob();
        $this->getQueue()->later($job, 2);
        sleep(2);
        $this->assertJobLaterDone($job, time());
    }

    protected function tearDown()
    {
        foreach (glob(Yii::getAlias("@runtime/queue/*")) as $fileName) {
            unlink($fileName);
        }
        parent::tearDown();
    }
}