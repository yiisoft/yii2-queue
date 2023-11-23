<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace tests\drivers\stomp;

use tests\app\RetryJob;
use tests\drivers\CliTestCase;
use Yii;
use yii\base\NotSupportedException;
use yii\queue\stomp\Queue;

final class QueueTest extends CliTestCase
{
    public function testListen(): void
    {
        $this->startProcess(['php', 'yii', 'queue/listen']);
        $job = $this->createSimpleJob();
        $this->getQueue()->push($job);

        $this->assertSimpleJobDone($job);
    }

    public function testRetry(): void
    {
        $this->startProcess(['php', 'yii', 'queue/listen']);
        $job = new RetryJob(['uid' => uniqid('', true)]);
        $this->getQueue()->push($job);
        sleep(6);

        $this->assertFileExists($job->getFileName());
        $this->assertEquals('aa', file_get_contents($job->getFileName()));
    }

    public function testStatus(): void
    {
        $this->expectException(NotSupportedException::class);

        $id = $this->getQueue()->push($this->createSimpleJob());
        $this->getQueue()->isWaiting($id);
    }

    protected function getQueue(): Queue
    {
        return Yii::$app->stompQueue;
    }
}
