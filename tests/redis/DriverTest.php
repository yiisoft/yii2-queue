<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace tests\redis;

use Yii;
use tests\DriverTestCase;

/**
 * Redis Driver Test
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class DriverTest extends DriverTestCase
{
    protected function getQueue()
    {
        return Yii::$app->redisQueue;
    }

    public function setUp()
    {
        parent::setUp();
        $this->getQueue()->driver->redis->executeCommand('FLUSHDB');
    }

    public function testRun()
    {
        $job = $this->createJob();
        $this->getQueue()->push($job);
        $this->runProcess('yii queue/run');
        $this->assertJobDone($job);
    }

    public function testListen()
    {
        $this->startProcess('yii queue/listen');
        $job = $this->createJob();
        $this->getQueue()->push($job);
        $this->assertJobDone($job);
    }
}