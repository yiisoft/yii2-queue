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
    }
}
