<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\beanstalk;

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
        Console::output($this->format("Statistical information about the tube:", Console::FG_GREEN));

        foreach ($this->queue->getStatsTube() as $key => $value) {
            Console::stdout($this->format("- $key: ", Console::FG_YELLOW));
            Console::output($value);
        }
    }
}