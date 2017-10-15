<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\stoppable;

use yii\caching\Cache;
use yii\di\Instance;

/**
 * Stoppable Behavior
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 * @since 2.0.1
 */
class Behavior extends BaseBehavior
{
    /**
     * @var Cache|array|string the cache instance used to store stopped status.
     */
    public $cache = 'cache';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->cache = Instance::ensure($this->cache, Cache::class);
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
        }

        return false;
    }
}
