<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace tests\beanstalk;

use Yii;
use tests\DriverTestCase;

/**
 * Beanstalk Driver Test
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class DriverTest extends DriverTestCase
{
    public function testRun()
    {
        $job = $this->createJob();
        Yii::$app->beanstalkQueue->push($job);
        $this->runProcess('php tests/app/yii.php beanstalk-queue/run');
        $this->assertJobDone($job);
    }

    public function testListen()
    {
        $this->startProcess('php tests/app/yii.php beanstalk-queue/listen');
        $job = $this->createJob();
        Yii::$app->beanstalkQueue->push($job);
        $this->assertJobDone($job);
    }

    public function testLater()
    {
        $this->startProcess('php tests/app/yii.php beanstalk-queue/listen');
        $job = $this->createJob();
        Yii::$app->beanstalkQueue->later($job, 2);
        sleep(2);
        $this->assertJobLaterDone($job, time());
    }
}