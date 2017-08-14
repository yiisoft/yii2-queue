<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\behaviors;

use yii\base\Behavior;
use yii\caching\Cache;
use yii\di\Instance;
use yii\queue\ExecEvent;
use yii\queue\Queue;

/**
 * StopBehavior allows stopping scheduled jobs in a queue.
 *
 * It provides a [[stop()]] method to mark scheduled jobs as "stopped", that
 * will prevent their execution.
 *
 * This behavior should be attached to the [[Queue]] component.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 * @since 2.0.1
 */
class StopBehavior extends Behavior
{
    /**
     * @var Cache|array|string the cache instance used to store stopped status.
     */
    public $cache = 'cache';
    /**
     * @var bool
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
    public function init()
    {
        parent::init();
        $this->cache = Instance::ensure($this->cache, Cache::class);
    }

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
        } else {
            return false;
        }
    }

    /**
     * @param string $id of a job
     * @return bool
     */
    protected function markAsStopped($id)
    {
        $this->cache->set(__CLASS__ . $id, true);
    }

    /**
     * @param string $id of a job
     * @return bool
     */
    protected function isStopped($id)
    {
        if ($this->cache->exists(__CLASS__ . $id)) {
            $this->cache->delete(__CLASS__ . $id);
            return true;
        } else {
            return false;
        }
    }
}
