<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace yii\queue\file;

use yii\helpers\BaseConsole;
use yii\helpers\Console;
use yii\queue\cli\Action;
use yii\queue\cli\Queue as CliQueue;

/**
 * Info about queue status.
 *
 * @deprecated Will be removed in 3.0. Use yii\queue\cli\InfoAction instead.
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
        Console::output($this->format('Jobs', BaseConsole::FG_GREEN));

        Console::stdout($this->format('- waiting: ', BaseConsole::FG_YELLOW));
        Console::output((string)$this->getWaitingCount());

        Console::stdout($this->format('- delayed: ', BaseConsole::FG_YELLOW));
        Console::output((string)$this->getDelayedCount());

        Console::stdout($this->format('- reserved: ', BaseConsole::FG_YELLOW));
        Console::output((string)$this->getReservedCount());

        Console::stdout($this->format('- done: ', BaseConsole::FG_YELLOW));
        Console::output((string)$this->getDoneCount());
    }

    /**
     * @return int
     */
    protected function getWaitingCount(): int
    {
        /** @var array{waiting: array} $data */
        $data = $this->getIndexData();
        return !empty($data['waiting']) ? count($data['waiting']) : 0;
    }

    /**
     * @return int
     */
    protected function getDelayedCount(): int
    {
        /** @var array{delayed: array} $data */
        $data = $this->getIndexData();
        return !empty($data['delayed']) ? count($data['delayed']) : 0;
    }

    /**
     * @return int
     */
    protected function getReservedCount(): int
    {
        /** @var array{reserved: array} $data */
        $data = $this->getIndexData();
        return !empty($data['reserved']) ? count($data['reserved']) : 0;
    }

    /**
     * @return int
     */
    protected function getDoneCount(): int
    {
        /** @var array{lastId: int} $data */
        $data = $this->getIndexData();
        $total = $data['lastId'] ?? 0;
        return $total - $this->getDelayedCount() - $this->getWaitingCount();
    }

    /**
     * @return array|mixed
     */
    protected function getIndexData(): mixed
    {
        static $data;
        if ($data === null) {
            $fileName = $this->queue->path . '/index.data';
            if (file_exists($fileName)) {
                $data = call_user_func($this->queue->indexDeserializer, file_get_contents($fileName));
            } else {
                $data = [];
            }
        }

        return $data;
    }
}
