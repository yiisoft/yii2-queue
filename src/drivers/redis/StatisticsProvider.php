<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue\redis;

use yii\base\BaseObject;
use yii\queue\interfaces\DelayedCountInterface;
use yii\queue\interfaces\DoneCountInterface;
use yii\queue\interfaces\ReservedCountInterface;
use yii\queue\interfaces\WaitingCountInterface;

/**
 * Statistics Provider
 *
 * @author Kalmer Kaurson <kalmerkaurson@gmail.com>
 */
class StatisticsProvider extends BaseObject implements DoneCountInterface, WaitingCountInterface, DelayedCountInterface, ReservedCountInterface
{
    /**
     * @var Queue
     */
    protected $queue;


    public function __construct(Queue $queue, $config = [])
    {
        $this->queue = $queue;
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function getWaitingCount()
    {
        $prefix = $this->queue->channel;
        return $this->queue->redis->llen("$prefix.waiting");
    }

    /**
     * @inheritdoc
     */
    public function getDelayedCount()
    {
        $prefix = $this->queue->channel;
        return $this->queue->redis->zcount("$prefix.delayed", '-inf', '+inf');
    }

    /**
     * @inheritdoc
     */
    public function getReservedCount()
    {
        $prefix = $this->queue->channel;
        return $this->queue->redis->zcount("$prefix.reserved", '-inf', '+inf');
    }

    /**
     * @inheritdoc
     */
    public function getDoneCount()
    {
        $prefix = $this->queue->channel;
        $waiting = $this->getWaitingCount();
        $delayed = $this->getDelayedCount();
        $reserved = $this->getReservedCount();
        $total = $this->queue->redis->get("$prefix.message_id");
        return $total - $waiting - $delayed - $reserved;
    }
}
