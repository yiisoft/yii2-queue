<?php

namespace tests\amqp;

use Yii;
use tests\Process;
use tests\DriverTestCase;
use yii\base\NotSupportedException;

/**
 * AMQP Driver Test
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class DriverTest extends DriverTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function testListen()
    {
        $pid = Process::start('php tests/app/yii.php amqp-queue/listen');
        $job = $this->createJob();
        Yii::$app->amqpQueue->push($job);
        $this->assertJobDone($job);
        Process::stop($pid);
    }

    public function testLater()
    {
        $this->expectException(NotSupportedException::class);
        Yii::$app->amqpQueue->later($this->createJob(), 2);
    }
}