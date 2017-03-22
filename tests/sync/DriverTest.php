<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace tests\sync;

use Yii;
use tests\DriverTestCase;

/**
 * Sync Driver Test
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class DriverTest extends DriverTestCase
{
    public function testRun()
    {
        $job = $this->createJob();
        Yii::$app->syncQueue->push($job);
        Yii::$app->syncQueue->driver->run();
        $this->assertJobDone($job);
    }
}