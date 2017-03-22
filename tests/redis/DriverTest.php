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
    public function setUp()
    {
        parent::setUp();
        Yii::$app->redis->executeCommand('FLUSHDB');
    }

    public function testRun()
    {
        $job = $this->createJob();
        Yii::$app->redisQueue->push($job);
        $this->runProcess('php tests/app/yii.php redis-queue/run');
        $this->assertJobDone($job);
    }

    public function testListen()
    {
        $this->startProcess('php tests/app/yii.php redis-queue/listen');
        $job = $this->createJob();
        Yii::$app->redisQueue->push($job);
        $this->assertJobDone($job);
    }
}