<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue;

use Yii;
use yii\base\Behavior;
use yii\base\Component;

/**
 * Log Behavior.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class LogBehavior extends Behavior
{
    /**
     * @var Queue|null|Component
     * @inheritdoc
     */
    public $owner;
    /**
     * @var bool
     */
    public bool $autoFlush = true;

    /**
     * @inheritdoc
     */
    public function events(): array
    {
        return [
            Queue::EVENT_AFTER_PUSH => 'afterPush',
            Queue::EVENT_BEFORE_EXEC => 'beforeExec',
            Queue::EVENT_AFTER_EXEC => 'afterExec',
            Queue::EVENT_AFTER_ERROR => 'afterError',
            cli\Queue::EVENT_WORKER_START => 'workerStart',
            cli\Queue::EVENT_WORKER_STOP => 'workerStop',
        ];
    }

    /**
     * @param PushEvent $event
     */
    public function afterPush(PushEvent $event): void
    {
        $title = $this->getJobTitle($event);
        Yii::info("$title is pushed.", Queue::class);
    }

    /**
     * @param ExecEvent $event
     */
    public function beforeExec(ExecEvent $event): void
    {
        $title = $this->getExecTitle($event);
        Yii::info("$title is started.", Queue::class);
        Yii::beginProfile($title, Queue::class);
    }

    /**
     * @param ExecEvent $event
     */
    public function afterExec(ExecEvent $event): void
    {
        $title = $this->getExecTitle($event);
        Yii::endProfile($title, Queue::class);
        Yii::info("$title is finished.", Queue::class);
        if ($this->autoFlush) {
            Yii::getLogger()->flush(true);
        }
    }

    /**
     * @param ExecEvent $event
     */
    public function afterError(ExecEvent $event): void
    {
        $title = $this->getExecTitle($event);
        Yii::endProfile($title, Queue::class);
        Yii::error("$title is finished with error: $event->error.", Queue::class);
        if ($this->autoFlush) {
            Yii::getLogger()->flush(true);
        }
    }

    /**
     * @param cli\WorkerEvent $event
     * @since 2.0.2
     */
    public function workerStart(cli\WorkerEvent $event): void
    {
        $workerPid = $event->sender->getWorkerPid();
        if (null === $workerPid) {
            $workerPid = '{PID not found}';
        }
        $title = 'Worker ' . $workerPid;
        Yii::info("$title is started.", Queue::class);
        Yii::beginProfile($title, Queue::class);

        if ($this->autoFlush) {
            Yii::getLogger()->flush(true);
        }
    }

    /**
     * @param cli\WorkerEvent $event
     * @since 2.0.2
     */
    public function workerStop(cli\WorkerEvent $event): void
    {
        $workerPid = $event->sender->getWorkerPid();
        if (null === $workerPid) {
            $workerPid = '{PID not found}';
        }
        $title = 'Worker ' . $workerPid;
        Yii::endProfile($title, Queue::class);
        Yii::info("$title is stopped.", Queue::class);

        if ($this->autoFlush) {
            Yii::getLogger()->flush(true);
        }
    }

    /**
     * @param JobEvent $event
     * @return string
     * @since 2.0.2
     */
    protected function getJobTitle(JobEvent $event): string
    {
        $name = $event->job instanceof JobInterface ? get_class($event->job) : 'unknown job';
        return "[$event->id] $name";
    }

    /**
     * @param ExecEvent $event
     * @return string
     * @since 2.0.2
     */
    protected function getExecTitle(ExecEvent $event): string
    {
        $title = $this->getJobTitle($event);
        $extra = "attempt: $event->attempt";

        $pid = $event->sender->getWorkerPid();
        if (null !== $pid) {
            $extra .= ", PID: $pid";
        }
        return "$title ($extra)";
    }
}
