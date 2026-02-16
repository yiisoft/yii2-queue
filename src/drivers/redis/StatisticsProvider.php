<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace yii\queue\redis;

use yii\base\BaseObject;
use yii\queue\interfaces\StatisticsInterface;

/**
 * Statistics Provider
 *
 * @author Kalmer Kaurson <kalmerkaurson@gmail.com>
 */
class StatisticsProvider extends BaseObject implements StatisticsInterface
{
    /**
     * @var Queue
     */
    protected Queue $queue;


    public function __construct(Queue $queue, array $config = [])
    {
        $this->queue = $queue;
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function getWaitingCount(): int
    {
        $prefix = $this->queue->channel;
        return (int) $this->queue->redis->llen("$prefix.waiting");
    }

    /**
     * @inheritdoc
     */
    public function getDelayedCount(): int
    {
        $prefix = $this->queue->channel;
        return (int) $this->queue->redis->zcount("$prefix.delayed", '-inf', '+inf');
    }

    /**
     * @inheritdoc
     */
    public function getReservedCount(): int
    {
        $prefix = $this->queue->channel;
        return (int) $this->queue->redis->zcount("$prefix.reserved", '-inf', '+inf');
    }

    /**
     * @inheritdoc
     */
    public function getDoneCount(): int
    {
        $prefix = $this->queue->channel;
        $waiting = $this->getWaitingCount();
        $delayed = $this->getDelayedCount();
        $reserved = $this->getReservedCount();
        $total = (int) $this->queue->redis->get("$prefix.message_id");
        return $total - $waiting - $delayed - $reserved;
    }
}
