<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\cli;

use yii\base\Behavior;
use yii\console\Controller;
use yii\helpers\Console;
use yii\queue\ErrorEvent;
use yii\queue\ExecEvent;

/**
 * Verbose Behavior
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class VerboseBehavior extends Behavior
{
    /**
     * @var Queue
     */
    public $owner;
    /**
     * @var Controller
     */
    public $command;

    private $start;


    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            Queue::EVENT_BEFORE_EXEC => 'beforeExec',
            Queue::EVENT_AFTER_EXEC => 'afterExec',
            Queue::EVENT_AFTER_ERROR => 'afterError',
        ];
    }

    public function beforeExec(ExecEvent $event)
    {
        $this->start = microtime(true);

        $title = $this->command->ansiFormat($this->formatTitle($event), Console::FG_YELLOW);
        $status = $this->command->ansiFormat('Started', Console::FG_GREEN);

        $this->command->stdout("$title - $status\n");
    }

    public function afterExec(ExecEvent $event)
    {
        $title = $this->command->ansiFormat($this->formatTitle($event), Console::FG_YELLOW);
        $status = $this->command->ansiFormat('Done', Console::FG_GREEN);
        $time = $this->command->ansiFormat(
            $this->formatTime(round(microtime(true) - $this->start, 3)),
            Console::FG_YELLOW
        );

        $this->command->stdout("$title - $status $time\n");
    }

    public function afterError(ErrorEvent $event)
    {
        $title = $this->command->ansiFormat($this->formatTitle($event), Console::FG_YELLOW);
        $status = $this->command->ansiFormat('Error', Console::BG_RED);
        $time = $this->command->ansiFormat(
            $this->formatTime(round(microtime(true) - $this->start, 3)),
            Console::FG_YELLOW
        );

        $this->command->stderr("$title - $status $time\n$event->error\n");
    }

    /**
     * @param ExecEvent $event
     * @return string
     */
    protected function formatTitle(ExecEvent $event)
    {
        return strtr('{time}: [{id}] {class} (attempt: {attempt})', [
            '{time}' => date('Y-m-d H:i:s'),
            '{id}' => $event->id,
            '{class}' => get_class($event->job),
            '{attempt}' => $event->attempt,
        ]);
    }

    /**
     * @param float $time
     * @return string
     */
    protected function formatTime($time)
    {
        return strtr('({time} s)', [
            '{time}' => $time
        ]);
    }
}