<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace tests\db;

use yii\db\Connection;

/**
 * Db Driver Test Case
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class DriverTestCase extends \tests\DriverTestCase
{
    /**
     * @return Connection
     */
    protected function getDb()
    {
        return $this->getQueue()->driver->db;
    }

    public function setUp()
    {
        parent::setUp();
        $this->getDb()->createCommand()
            ->delete($this->getQueue()->driver->tableName)
            ->execute();
    }

    public function testRun()
    {
        $job = $this->createJob();
        $this->getQueue()->push($job);
        $this->runProcess('yii queue/run');
        $this->assertJobDone($job);
    }

    public function testListen()
    {
        $this->startProcess('yii queue/listen');
        $job = $this->createJob();
        $this->getQueue()->push($job);
        $this->assertJobDone($job);
    }

    public function testLater()
    {
        $this->startProcess('yii queue/listen');
        $job = $this->createJob();
        $this->getQueue()->later($job, 2);
        sleep(2);
        $this->assertJobLaterDone($job, time());
    }

}