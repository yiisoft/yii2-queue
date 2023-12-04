<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue\db;

use yii\db\Connection;
use yii\db\Query;
use yii\helpers\BaseConsole;
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
     * @psalm-suppress PropertyNotSetInConstructor, NonInvariantDocblockPropertyType
     */
    public CliQueue $queue;

    /**
     * Info about queue status.
     */
    public function run(): void
    {
        Console::output($this->format('Jobs', BaseConsole::FG_GREEN));
        /** @var Connection $db */
        $db = $this->queue->db;

        Console::stdout($this->format('- waiting: ', BaseConsole::FG_YELLOW));
        Console::output((string)$this->getWaiting()->count('*', $db));

        Console::stdout($this->format('- delayed: ', BaseConsole::FG_YELLOW));
        Console::output((string)$this->getDelayed()->count('*', $db));

        Console::stdout($this->format('- reserved: ', BaseConsole::FG_YELLOW));
        Console::output((string)$this->getReserved()->count('*', $db));

        Console::stdout($this->format('- done: ', BaseConsole::FG_YELLOW));
        Console::output((string)$this->getDone()->count('*', $db));
    }

    /**
     * @return Query
     */
    protected function getWaiting(): Query
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
    protected function getDelayed(): Query
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
    protected function getReserved(): Query
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
    protected function getDone(): Query
    {
        return (new Query())
            ->from($this->queue->tableName)
            ->andWhere(['channel' => $this->queue->channel])
            ->andWhere('[[done_at]] is not null');
    }
}
