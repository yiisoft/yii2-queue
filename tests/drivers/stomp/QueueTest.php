<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tests\drivers\stomp;

use tests\app\RetryJob;
use tests\drivers\CliTestCase;
use Yii;
use yii\queue\stomp\Queue;


class QueueTest extends CliTestCase
{
    public function testListen()
    {
        $this->startProcess(['php', 'yii', 'queue/listen']);
        $job = $this->createSimpleJob();
        $this->getQueue()->push($job);

        $this->assertSimpleJobDone($job);
    }

    public function testRetry()
    {
        $this->startProcess(['php', 'yii', 'queue/listen']);
        $job = new RetryJob(['uid' => uniqid()]);
        $this->getQueue()->push($job);
        sleep(6);

        $this->assertFileExists($job->getFileName());
        $this->assertEquals('aa', file_get_contents($job->getFileName()));
    }

    /**
     * @return Queue
     */
    protected function getQueue()
    {
        return Yii::$app->stompQueue;
    }


    protected function setUp()
    {
        if ('true' == getenv('EXCLUDE_STOMP')) {
            $this->markTestSkipped('Stomp tests are disabled for php 5.5');
        }

        parent::setUp();
    }

}
