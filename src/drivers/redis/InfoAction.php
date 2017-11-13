<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\redis;

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
        $prefix = $this->queue->channel;
        $waiting = $this->queue->redis->llen("$prefix.waiting");
        $delayed = $this->queue->redis->zcount("$prefix.delayed", '-inf', '+inf');
        $reserved = $this->queue->redis->zcount("$prefix.reserved", '-inf', '+inf');
        $total = $this->queue->redis->get("$prefix.message_id");
        $done = $total - $waiting - $delayed - $reserved;

        Console::output($this->format('Jobs', Console::FG_GREEN));

        Console::stdout($this->format('- waiting: ', Console::FG_YELLOW));
        Console::output($waiting);

        Console::stdout($this->format('- delayed: ', Console::FG_YELLOW));
        Console::output($delayed);

        Console::stdout($this->format('- reserved: ', Console::FG_YELLOW));
        Console::output($reserved);

        Console::stdout($this->format('- done: ', Console::FG_YELLOW));
        Console::output($done);

        if ($workersInfo = $this->getWorkersInfo()) {
            Console::output($this->format('Workers ', Console::FG_GREEN));
            foreach ($workersInfo as $name => $info) {
                Console::stdout($this->format("- $name: ", Console::FG_YELLOW));
                Console::output($info['addr']);
            }
        }
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