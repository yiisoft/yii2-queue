<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace tests\db;

use Yii;
use tests\DriverTestCase;

/**
 * Db Driver Test
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class DriverTest extends DriverTestCase
{
    public function setUp()
    {
        parent::setUp();
        Yii::$app->db->createCommand()
            ->delete(Yii::$app->dbQueue->driver->tableName)
            ->execute();
    }

    public function testRun()
    {
        $job = $this->createJob();
        Yii::$app->dbQueue->push($job);
        $this->runProcess('php tests/app/yii.php db-queue/run');
        $this->assertJobDone($job);
    }

    public function testListen()
    {
        $this->startProcess('php tests/app/yii.php db-queue/listen');
        $job = $this->createJob();
        Yii::$app->dbQueue->push($job);
        $this->assertJobDone($job);
    }

    public function testLater()
    {
        $this->startProcess('php tests/app/yii.php db-queue/listen');
        $job = $this->createJob();
        Yii::$app->dbQueue->later($job, 2);
        sleep(2);
        $this->assertJobLaterDone($job, time());
    }
}