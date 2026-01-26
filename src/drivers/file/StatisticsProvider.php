<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace yii\queue\file;

use yii\base\BaseObject;
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
        /** @var array{waiting:array} $data */
        $data = $this->getIndexData();
        return !empty($data['waiting']) ? count($data['waiting']) : 0;
    }

    /**
     * @inheritdoc
     */
    public function getDelayedCount(): int
    {
        /** @var array{delayed:array} $data */
        $data = $this->getIndexData();
        return !empty($data['delayed']) ? count($data['delayed']) : 0;
    }

    /**
     * @inheritdoc
     */
    public function getReservedCount(): int
    {
        /** @var array{reserved:array} $data */
        $data = $this->getIndexData();
        return !empty($data['reserved']) ? count($data['reserved']) : 0;
    }

    /**
     * @inheritdoc
     */
    public function getDoneCount(): int
    {
        /** @var array{lastId:int} $data */
        $data = $this->getIndexData();
        $total = isset($data['lastId']) ? $data['lastId'] : 0;
        return $total - $this->getDelayedCount() - $this->getWaitingCount();
    }

    /**
     * @psalm-suppress MissingReturnType
     */
    protected function getIndexData()
    {
        $fileName = $this->queue->path . '/index.data';
        if (file_exists($fileName)) {
            return call_user_func($this->queue->indexDeserializer, file_get_contents($fileName));
        }

        return [];
    }
}
