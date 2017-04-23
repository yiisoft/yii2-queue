<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace tests\drivers\db;

/**
 * Db Queue Test Case
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class TestCase extends \tests\drivers\TestCase
{
    public function testRun()
    {
        $job = $this->createJob();
        $id = $this->getQueue()->push($job);
        $this->runProcess('php tests/yii queue/run');
        $this->assertJobDone($job, $id);
    }

    public function testListen()
    {
        $this->startProcess('php tests/yii queue/listen');
        $job = $this->createJob();
        $id = $this->getQueue()->push($job);
        $this->assertJobDone($job, $id);
    }

    public function testLater()
    {
        $this->startProcess('php tests/yii queue/listen');
        $job = $this->createJob();
        $id = $this->getQueue()->later($job, 2);
        sleep(2);
        $this->assertJobLaterDone($job, $id, time());
    }

    protected function tearDown()
    {
        $this->getQueue()->db->createCommand()
            ->delete($this->getQueue()->tableName)
            ->execute();
        parent::tearDown();
    }
}