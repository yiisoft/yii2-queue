<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\file;

use yii\helpers\Console;
use yii\queue\cli\Action;

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
    public $queue;


    /**
     * Info about queue status.
     */
    public function run()
    {
        Console::output($this->format('Jobs', Console::FG_GREEN));

        Console::stdout($this->format('- waiting: ', Console::FG_YELLOW));
        Console::output($this->getWaitingCount());

        Console::stdout($this->format('- delayed: ', Console::FG_YELLOW));
        Console::output($this->getDelayedCount());

        Console::stdout($this->format('- reserved: ', Console::FG_YELLOW));
        Console::output($this->getReservedCount());

        Console::stdout($this->format('- done: ', Console::FG_YELLOW));
        Console::output($this->getDoneCount());
    }

    /**
     * @return int
     */
    protected function getWaitingCount()
    {
        $data = $this->getIndexData();
        $count = !empty($data['waiting']) ? count($data['waiting']) : 0;

        return $count;
    }

    /**
     * @return int
     */
    protected function getDelayedCount()
    {
        $data = $this->getIndexData();
        $count = !empty($data['delayed']) ? count($data['delayed']) : 0;

        return $count;
    }

    /**
     * @return int
     */
    protected function getReservedCount()
    {
        $data = $this->getIndexData();
        $count = !empty($data['reserved']) ? count($data['reserved']) : 0;

        return $count;
    }

    /**
     * @return int
     */
    protected function getDoneCount()
    {
        $data = $this->getIndexData();
        $total = isset($data['lastId']) ? $data['lastId'] : 0;
        $done = $total - $this->getDelayedCount() - $this->getWaitingCount();

        return $done;
    }

    protected function getIndexData()
    {
        static $data;
        if ($data === null) {
            $fileName = $this->queue->path . '/index.data';
            if (file_exists($fileName)) {
                $data = $this->queue->serializer->unserialize(file_get_contents($fileName));
            } else {
                $data = [];
            }
        }

        return $data;
    }
}
