<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\cli;

use yii\base\Behavior;
use yii\helpers\Console;
use zhuravljov\yii\queue\ErrorEvent;
use zhuravljov\yii\queue\JobEvent;

/**
 * Verbose Behavior
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Verbose extends Behavior
{
    /**
     * @var Queue
     */
    public $owner;

    private $start;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            Queue::EVENT_BEFORE_EXEC => 'beforeExec',
            Queue::EVENT_AFTER_EXEC => 'afterExec',
            Queue::EVENT_AFTER_EXEC_ERROR => 'afterExecError',
        ];
    }

    public function beforeExec(JobEvent $event)
    {
        $this->start = microtime(true);
        Console::stdout(strtr('{time}: [{id}] {class} has been started ... ', [
            '{time}' => date('Y-m-d H:i:s'),
            '{id}' => $event->id,
            '{class}' => get_class($event->job),
        ]));
    }

    public function afterExec(JobEvent $event)
    {
        Console::output(strtr('Done ({time} s)', [
            '{time}' => round(microtime(true) - $this->start, 3),
        ]));
    }

    public function afterExecError(ErrorEvent $event)
    {
        Console::output(strtr('Error ({time} s)', [
            '{time}' => round(microtime(true) - $this->start, 3),
        ]));
        Console::error($event->error);
    }
}