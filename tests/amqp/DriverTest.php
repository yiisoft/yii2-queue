<?php

namespace tests\amqp;

use Yii;
use tests\Process;
use tests\DriverTestCase;

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
        $this->startProcess('php tests/app/yii.php amqp-queue/listen');
        $job = $this->createJob();
        Yii::$app->amqpQueue->push($job);
        $this->assertJobDone($job);
    }
}