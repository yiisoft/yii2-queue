<?php

namespace tests\db;

use Yii;
use tests\Process;
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
        Process::run('php tests/app/yii.php db-queue/run');
        $this->assertJobDone($job);
    }

    public function testListen()
    {
        $pid = Process::start('php tests/app/yii.php db-queue/listen');
        $job = $this->createJob();
        Yii::$app->dbQueue->push($job);
        $this->assertJobDone($job);
        Process::stop($pid);
    }
}