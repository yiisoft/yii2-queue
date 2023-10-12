<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue\file;

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
        Console::output((string)$this->getWaitingCount());

        Console::stdout($this->format('- delayed: ', Console::FG_YELLOW));
        Console::output((string)$this->getDelayedCount());

        Console::stdout($this->format('- reserved: ', Console::FG_YELLOW));
        Console::output((string)$this->getReservedCount());

        Console::stdout($this->format('- done: ', Console::FG_YELLOW));
        Console::output((string)$this->getDoneCount());
    }

    /**
     * @return int
     */
    protected function getWaitingCount(): int
    {
        $data = $this->getIndexData();
        return !empty($data['waiting']) ? count($data['waiting']) : 0;
    }

    /**
     * @return int
     */
    protected function getDelayedCount(): int
    {
        $data = $this->getIndexData();
        return !empty($data['delayed']) ? count($data['delayed']) : 0;
    }

    /**
     * @return int
     */
    protected function getReservedCount(): int
    {
        $data = $this->getIndexData();
        return !empty($data['reserved']) ? count($data['reserved']) : 0;
    }

    /**
     * @return int
     */
    protected function getDoneCount(): int
    {
        $data = $this->getIndexData();
        $total = $data['lastId'] ?? 0;
        return $total - $this->getDelayedCount() - $this->getWaitingCount();
    }

    protected function getIndexData()
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
