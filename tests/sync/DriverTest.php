<?php

namespace tests\sync;

use Yii;
use tests\DriverTestCase;
use yii\base\NotSupportedException;

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

    public function testLater()
    {
        $this->expectException(NotSupportedException::class);
        Yii::$app->amqpQueue->later($this->createJob(), 2);
    }
}