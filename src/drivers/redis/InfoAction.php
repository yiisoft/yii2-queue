<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\drivers\redis;

use yii\helpers\Console;
use zhuravljov\yii\queue\cli\Action;

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
        Console::output(Console::ansiFormat('Jobs', [Console::FG_GREEN]));

        Console::stdout(Console::ansiFormat('- reserved: ', [Console::FG_YELLOW]));
        Console::output($this->getReservedCount());

        Console::stdout(Console::ansiFormat('- delayed: ', [Console::FG_YELLOW]));
        Console::output($this->getDelayedCount());

        if ($workersInfo = $this->getWorkersInfo()) {
            Console::output(Console::ansiFormat('Workers ', [Console::FG_GREEN]));
            foreach ($workersInfo as $name => $info) {
                Console::stdout(Console::ansiFormat("- $name: ", [Console::FG_YELLOW]));
                Console::output($info['addr']);
            }
        }
    }

    /**
     * @return integer
     */
    protected function getReservedCount()
    {
        return $this->queue->redis->llen($this->queue->channel . '.reserved');
    }

    /**
     * @return integer
     */
    protected function getDelayedCount()
    {
        return $this->queue->redis->zcount($this->queue->channel . '.delayed', '-inf', '+inf');
    }

    /**
     * @return array
     */
    protected function getWorkersInfo()
    {
        $workers = [];
        $data = $this->queue->redis->clientList();
        foreach (explode("\n", trim($data)) as $line) {
            $client = [];
            foreach (explode(' ', trim($line)) as $pair) {
                list($key, $value) = explode('=', $pair, 2);
                $client[$key] = $value;
            }
            if (isset($client['name']) && strpos($client['name'], $this->queue->channel . '.worker') === 0) {
                $workers[$client['name']] = $client;
            }
        }

        return $workers;
    }
}