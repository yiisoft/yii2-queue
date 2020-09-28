<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tests\drivers\gearman;

use tests\app\PriorityJob;
use tests\drivers\CliTestCase;
use Yii;
use yii\queue\gearman\Queue;

/**
 * Gearman Queue Test.
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

    public function testPriority()
    {
        $this->getQueue()->priority('high')->push(new PriorityJob(['number' => 1]));
        $this->getQueue()->priority('low')->push(new PriorityJob(['number' => 5]));
        $this->getQueue()->priority('norm')->push(new PriorityJob(['number' => 3]));
        $this->getQueue()->priority('norm')->push(new PriorityJob(['number' => 4]));
        $this->getQueue()->priority('high')->push(new PriorityJob(['number' => 2]));
        $this->runProcess(['php', 'yii', 'queue/run']);

        $this->assertEquals('12345', file_get_contents(PriorityJob::getFileName()));
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
        $this->startProcess(['php', 'yii', 'queue/listen']);
        $job = $this->createSimpleJob();
        $this->getQueue()->push($job);

        $this->assertSimpleJobDone($job);
    }

    /**
     * @return Queue
     */
    protected function getQueue()
    {
        return Yii::$app->gearmanQueue;
    }

    public function setUp()
    {
        if (!defined('GEARMAN_SUCCESS')) {
            $this->markTestSkipped('Gearman in not installed.');
        }

        parent::setUp();
    }
}
