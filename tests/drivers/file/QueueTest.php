<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tests\drivers\file;

use tests\app\RetryJob;
use tests\drivers\CliTestCase;
use Yii;
use yii\queue\file\Queue;

/**
 * File Queue Test.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class QueueTest extends CliTestCase
{
    public function testRun()
    {
        $job = $this->createSimpleJob();
        $this->getQueue()->push($job);
        $this->runProcess(['php', 'yii', 'queue/run']);

        $this->assertSimpleJobDone($job);
    }

    public function testStatus()
    {
        $job = $this->createSimpleJob();
        $id = $this->getQueue()->push($job);
        $isWaiting = $this->getQueue()->isWaiting($id);
        $this->runProcess(['php', 'yii', 'queue/run']);
        $isDone = $this->getQueue()->isDone($id);

        $this->assertTrue($isWaiting);
        $this->assertTrue($isDone);
    }

    public function testListen()
    {
        $this->startProcess(['php', 'yii', 'queue/listen', '1']);
        $job = $this->createSimpleJob();
        $this->getQueue()->push($job);

        $this->assertSimpleJobDone($job);
    }

    public function testLater()
    {
        $this->startProcess(['php', 'yii', 'queue/listen', '1']);
        $job = $this->createSimpleJob();
        $this->getQueue()->delay(2)->push($job);

        $this->assertSimpleJobLaterDone($job, 2);
    }

    public function testRetry()
    {
        $this->startProcess(['php', 'yii', 'queue/listen', '1']);
        $job = new RetryJob(['uid' => uniqid()]);
        $this->getQueue()->push($job);
        sleep(6);

        $this->assertFileExists($job->getFileName());
        $this->assertEquals('aa', file_get_contents($job->getFileName()));
    }

    public function testClear()
    {
        $this->getQueue()->push($this->createSimpleJob());
        $this->assertNotEmpty(glob($this->getQueue()->path . '/job*.data'));
        $this->runProcess(['php', 'yii', 'queue/clear', '--interactive=0']);

        $this->assertEmpty(glob($this->getQueue()->path . '/job*.data'));
    }

    public function testRemove()
    {
        $id = $this->getQueue()->push($this->createSimpleJob());
        $this->assertFileExists($this->getQueue()->path . "/job$id.data");
        $this->runProcess(['php', 'yii', 'queue/remove', $id]);

        $this->assertFileNotExists($this->getQueue()->path . "/job$id.data");
    }

    /**
     * @return Queue
     */
    protected function getQueue()
    {
        return Yii::$app->fileQueue;
    }

    protected function tearDown()
    {
        foreach (glob(Yii::getAlias("@runtime/queue/*")) as $fileName) {
            unlink($fileName);
        }
        parent::tearDown();
    }
}
