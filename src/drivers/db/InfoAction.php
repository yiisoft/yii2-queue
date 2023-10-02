<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue\db;

use yii\db\Query;
use yii\helpers\Console;
use yii\queue\cli\Action;
use yii\queue\cli\Queue as CliQueue;

/**
 * Info about queue status.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class InfoAction extends Action
{
    /**
     * @var Queue
     */
    public CliQueue $queue;

    /**
     * Info about queue status.
     */
    public function run(): void
    {
        Console::output($this->format('Jobs', Console::FG_GREEN));

        Console::stdout($this->format('- waiting: ', Console::FG_YELLOW));
        Console::output($this->getWaiting()->count('*', $this->queue->db));

        Console::stdout($this->format('- delayed: ', Console::FG_YELLOW));
        Console::output($this->getDelayed()->count('*', $this->queue->db));

        Console::stdout($this->format('- reserved: ', Console::FG_YELLOW));
        Console::output($this->getReserved()->count('*', $this->queue->db));

        Console::stdout($this->format('- done: ', Console::FG_YELLOW));
        Console::output($this->getDone()->count('*', $this->queue->db));
    }

    /**
     * @return Query
     */
    protected function getWaiting()
    {
        return (new Query())
            ->from($this->queue->tableName)
            ->andWhere(['channel' => $this->queue->channel])
            ->andWhere(['reserved_at' => null])
            ->andWhere(['delay' => 0]);
    }

    /**
     * @return Query
     */
    protected function getDelayed()
    {
        return (new Query())
            ->from($this->queue->tableName)
            ->andWhere(['channel' => $this->queue->channel])
            ->andWhere(['reserved_at' => null])
            ->andWhere(['>', 'delay', 0]);
    }

    /**
     * @return Query
     */
    protected function getReserved()
    {
        return (new Query())
            ->from($this->queue->tableName)
            ->andWhere(['channel' => $this->queue->channel])
            ->andWhere('[[reserved_at]] is not null')
            ->andWhere(['done_at' => null]);
    }

    /**
     * @return Query
     */
    protected function getDone()
    {
        return (new Query())
            ->from($this->queue->tableName)
            ->andWhere(['channel' => $this->queue->channel])
            ->andWhere('[[done_at]] is not null');
    }
}
