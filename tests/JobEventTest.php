<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tests;

use yii\queue\closure\Behavior as ClosureBehavior;
use yii\queue\ExecEvent;
use yii\queue\InvalidJobException;
use yii\queue\JobEvent;
use yii\queue\Queue;
use yii\queue\sync\Queue as SyncQueue;

/**
 * Job Event Test
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class JobEventTest extends TestCase
{
    public function testInvalidJob()
    {
        $eventCounter = [];
        $eventHandler = function (JobEvent $event) use (&$eventCounter) {
            $eventCounter[$event->id][$event->name] = true;
        };
        $queue = new SyncQueue(['strictJobType' => false]);
        $queue->on(Queue::EVENT_BEFORE_EXEC, $eventHandler);
        $queue->on(Queue::EVENT_AFTER_ERROR, $eventHandler);
        $queue->on(Queue::EVENT_AFTER_ERROR, function (ExecEvent $event) {
            $this->assertTrue($event->error instanceof InvalidJobException);
            $this->assertFalse($event->retry);
        });
        $jobId = $queue->push('message that cannot be unserialized');
        $queue->run();
        $this->assertArrayHasKey($jobId, $eventCounter);
        $this->assertArrayHasKey(Queue::EVENT_BEFORE_EXEC, $eventCounter[$jobId]);
        $this->assertArrayHasKey(Queue::EVENT_AFTER_ERROR, $eventCounter[$jobId]);
    }

    public function testExecResult()
    {
        $queue = new SyncQueue(['as closure' => ClosureBehavior::class]);
        $isTriggered = false;
        $queue->on(Queue::EVENT_AFTER_EXEC, function (ExecEvent $event) use (&$isTriggered) {
            $isTriggered = true;
            $this->assertSame(12345, $event->result);
        });
        $queue->push(function () {
            return 12345;
        });
        $queue->run();
        $this->assertTrue($isTriggered);
    }
}
