<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\stoppable;

use yii\queue\ExecEvent;
use yii\queue\Queue;

/**
 * Stoppable Behavior allows stopping scheduled jobs in a queue.
 *
 * It provides a [[stop()]] method to mark scheduled jobs as "stopped", that
 * will prevent their execution.
 *
 * This behavior should be attached to the [[Queue]] component.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 * @since 2.0.1
 */
abstract class BaseBehavior extends \yii\base\Behavior
{
    /**
     * @var bool option allows to turn status checking off in case a driver does not support it.
     */
    public $checkWaiting = true;
    /**
     * @var Queue
     * @inheritdoc
     */
    public $owner;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            Queue::EVENT_BEFORE_EXEC => 'beforeExec',
        ];
    }

    /**
     * @param ExecEvent $event
     */
    public function beforeExec(ExecEvent $event)
    {
        $event->handled = $this->isStopped($event->id);
    }

    /**
     * Sets stop flag.
     *
     * @param string $id of a job
     * @return bool
     */
    public function stop($id)
    {
        if (!$this->checkWaiting || $this->owner->isWaiting($id)) {
            $this->markAsStopped($id);
            return true;
        }

        return false;
    }

    /**
     * @param string $id of a job
     * @return bool
     */
    abstract protected function markAsStopped($id);

    /**
     * @param string $id of a job
     * @return bool
     */
    abstract protected function isStopped($id);
}
