<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace yii\queue\db;

use yii\base\BaseObject;
use yii\db\Query;
use yii\queue\interfaces\DelayedCountInterface;
use yii\queue\interfaces\DoneCountInterface;
use yii\queue\interfaces\ReservedCountInterface;
use yii\queue\interfaces\StatisticsInterface;
use yii\queue\interfaces\WaitingCountInterface;

/**
 * Statistics Provider
 *
 * @author Kalmer Kaurson <kalmerkaurson@gmail.com>
 */
class StatisticsProvider extends BaseObject implements
    DoneCountInterface,
    WaitingCountInterface,
    DelayedCountInterface,
    ReservedCountInterface,
    StatisticsInterface
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
        return (int) (new Query())
            ->from($this->queue->tableName)
            ->andWhere(['channel' => $this->queue->channel])
            ->andWhere(['reserved_at' => null])
            ->andWhere(['delay' => 0])
            ->count('*', $this->queue->db);
    }

    /**
     * @inheritdoc
     */
    public function getDelayedCount(): int
    {
        return (int) (new Query())
            ->from($this->queue->tableName)
            ->andWhere(['channel' => $this->queue->channel])
            ->andWhere(['reserved_at' => null])
            ->andWhere(['>', 'delay', 0])
            ->count('*', $this->queue->db);
    }

    /**
     * @inheritdoc
     */
    public function getReservedCount(): int
    {
        return (int) (new Query())
            ->from($this->queue->tableName)
            ->andWhere(['channel' => $this->queue->channel])
            ->andWhere('[[reserved_at]] is not null')
            ->andWhere(['done_at' => null])
            ->count('*', $this->queue->db);
    }

    /**
     * @inheritdoc
     */
    public function getDoneCount(): int
    {
        return (int) (new Query())
            ->from($this->queue->tableName)
            ->andWhere(['channel' => $this->queue->channel])
            ->andWhere('[[done_at]] is not null')
            ->count('*', $this->queue->db);
    }
}
