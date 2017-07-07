<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue;

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
            Queue::EVENT_AFTER_EXEC_ERROR => 'afterExecError',
        ];
    }

    public function afterPush(PushEvent $event)
    {
        Yii::info($this->getEventTitle($event) . ' pushed.', __NAMESPACE__ . "\\" . get_class(new Queue()));
    }

    public function beforeExec(JobEvent $event)
    {
        $class = __NAMESPACE__ . "\\" . get_class(new Queue());
        Yii::info($this->getEventTitle($event) . ' started.', $class);
        Yii::beginProfile($this->getEventTitle($event), $class);
    }

    public function afterExec(JobEvent $event)
    {
        $class = __NAMESPACE__ . "\\" . get_class(new Queue());
        Yii::endProfile($this->getEventTitle($event), $class);
        Yii::info($this->getEventTitle($event) . ' finished.', $class);
        if ($this->autoFlush) {
            Yii::getLogger()->flush(true);
        }
    }

    public function afterExecError(ErrorEvent $event)
    {
        $class = __NAMESPACE__ . "\\" . get_class(new Queue());
        Yii::endProfile($this->getEventTitle($event), $class);
        Yii::error($this->getEventTitle($event) . ' error ' . $event->error, $class);
        if ($this->autoFlush) {
            Yii::getLogger()->flush(true);
        }
    }

    protected function getEventTitle(JobEvent $event)
    {
        return strtr('[id] name', [
            'id' => $event->id,
            'name' => $event->job instanceof Job ? get_class($event->job) : 'mixed data',
        ]);
    }
}