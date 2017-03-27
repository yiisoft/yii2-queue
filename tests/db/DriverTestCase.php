<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace tests\db;

use yii\db\Connection;
use yii\helpers\Inflector;
use zhuravljov\yii\queue\Queue;

/**
 * Db Driver Test Case
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class DriverTestCase extends \tests\DriverTestCase
{
    /**
     * @return Queue
     */
    abstract protected function getQueue();

    /**
     * @return Connection
     */
    protected function getDb()
    {
        return $this->getQueue()->driver->db;
    }

    protected function runProcess($cmd)
    {
        parent::runProcess(strtr($cmd, [
            'queue' => Inflector::camel2id($this->getQueue()->id),
        ]));
    }

    protected function startProcess($cmd)
    {
        parent::startProcess(strtr($cmd, [
            'queue' => Inflector::camel2id($this->getQueue()->id),
        ]));
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
        $this->runProcess('php tests/app/yii.php queue/run');
        $this->assertJobDone($job);
    }

    public function testListen()
    {
        $this->startProcess('php tests/app/yii.php queue/listen');
        $job = $this->createJob();
        $this->getQueue()->push($job);
        $this->assertJobDone($job);
    }

    public function testLater()
    {
        $this->startProcess('php tests/app/yii.php queue/listen');
        $job = $this->createJob();
        $this->getQueue()->later($job, 2);
        sleep(2);
        $this->assertJobLaterDone($job, time());
    }

}