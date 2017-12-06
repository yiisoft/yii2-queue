<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue;

use Yii;
use yii\base\Behavior;

/**
 * Class LogBehavior
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class LogBehavior extends Behavior
{
    /**
     * @var Queue
     */
    public $owner;
    /**
     * @var bool
     */
    public $autoFlush = true;


    /**
     * @inheritdoc
     */
    public function events()
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
    public function afterPush(PushEvent $event)
    {
        $title = $this->getJobEventTitle($event);
        Yii::info("$title is pushed.", Queue::class);
    }

    /**
     * @param ExecEvent $event
     */
    public function beforeExec(ExecEvent $event)
    {
        $title = $this->getJobEventTitle($event);
        Yii::info("$title is started.", Queue::class);
        Yii::beginProfile($title, Queue::class);
    }

    /**
     * @param ExecEvent $event
     */
    public function afterExec(ExecEvent $event)
    {
        $title = $this->getJobEventTitle($event);
        Yii::endProfile($title, Queue::class);
        Yii::info("$title is finished.", Queue::class);
        if ($this->autoFlush) {
            Yii::getLogger()->flush(true);
        }
    }

    /**
     * @param ErrorEvent $event
     */
    public function afterError(ErrorEvent $event)
    {
        $title = $this->getJobEventTitle($event);
        Yii::endProfile($title, Queue::class);
        Yii::error("$title is finished with error: $event->error.", Queue::class);
        if ($this->autoFlush) {
            Yii::getLogger()->flush(true);
        }
    }

    /**
     * @param JobEvent $event
     * @return string
     */
    protected function getJobEventTitle(JobEvent $event)
    {
        $title = strtr('[id] name', [
            'id' => $event->id,
            'name' => $event->job instanceof JobInterface
                ? get_class($event->job)
                : 'mixed data',
        ]);
        if ($event instanceof ExecEvent) {
            $title .= " (attempt: $event->attempt, PID: $event->workerPid)";
        }

        return $title;
    }

    /**
     * @param cli\WorkerEvent $event
     * @since 2.0.2
     */
    public function workerStart(cli\WorkerEvent $event)
    {
        $title = $this->getWorkerEventTitle($event);
        Yii::endProfile($title, Queue::class);
        Yii::info("$title is started.", Queue::class);
        if ($this->autoFlush) {
            Yii::getLogger()->flush(true);
        }
    }

    /**
     * @param cli\WorkerEvent $event
     * @since 2.0.2
     */
    public function workerStop(cli\WorkerEvent $event)
    {
        $title = $this->getWorkerEventTitle($event);
        Yii::info("$title is stopped.", Queue::class);
        Yii::beginProfile($title, Queue::class);
        if ($this->autoFlush) {
            Yii::getLogger()->flush(true);
        }
    }

    /**
     * @param cli\WorkerEvent $event
     * @return string
     * @since 2.0.2
     */
    protected function getWorkerEventTitle(cli\WorkerEvent $event)
    {
        return strtr('command (PID: pid)', [
            'command' => $event->action->uniqueId,
            'pid' => $event->pid,
        ]);
    }
}
