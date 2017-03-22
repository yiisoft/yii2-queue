<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace tests\amqp;

use Yii;
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