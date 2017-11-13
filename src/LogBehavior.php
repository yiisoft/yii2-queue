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
        ];
    }

    public function afterPush(PushEvent $event)
    {
        Yii::info($this->getEventTitle($event) . ' pushed.', Queue::class);
    }

    public function beforeExec(ExecEvent $event)
    {
        Yii::info($this->getEventTitle($event) . ' started.', Queue::class);
        Yii::beginProfile($this->getEventTitle($event), Queue::class);
    }

    public function afterExec(ExecEvent $event)
    {
        Yii::endProfile($this->getEventTitle($event), Queue::class);
        Yii::info($this->getEventTitle($event) . ' finished.', Queue::class);
        if ($this->autoFlush) {
            Yii::getLogger()->flush(true);
        }
    }

    public function afterError(ExecEvent $event)
    {
        Yii::endProfile($this->getEventTitle($event), Queue::class);
        Yii::error($this->getEventTitle($event) . ' error ' . $event->error, Queue::class);
        if ($this->autoFlush) {
            Yii::getLogger()->flush(true);
        }
    }

    protected function getEventTitle(JobEvent $event)
    {
        $title = strtr('[id] name', [
            'id' => $event->id,
            'name' => $event->job instanceof JobInterface
                ? get_class($event->job)
                : 'mixed data',
        ]);
        if ($event instanceof ExecEvent) {
            $title .= " (attempt: $event->attempt)";
        }

        return $title;
    }
}