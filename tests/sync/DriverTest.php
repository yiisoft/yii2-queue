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
    protected function getQueue()
    {
        return Yii::$app->syncQueue;
    }

    public function testRun()
    {
        $job = $this->createJob();
        $this->getQueue()->push($job);
        $this->getQueue()->driver->run();
        $this->assertJobDone($job);
    }
}