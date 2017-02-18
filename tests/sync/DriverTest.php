<?php

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
        Yii::$app->syncQueue->push($job);
        Yii::$app->syncQueue->driver->run();
        $this->assertJobDone($job);
    }
}