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
    /**
     * @var float timestamp
     */
    private $jobStartedAt;
    /**
     * @var int timestamp
     */
    private $workerStartedAt;


    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            Queue::EVENT_BEFORE_EXEC => 'beforeExec',
            Queue::EVENT_AFTER_EXEC => 'afterExec',
            Queue::EVENT_AFTER_ERROR => 'afterError',
            Queue::EVENT_WORKER_START => 'workerStart',
            Queue::EVENT_WORKER_STOP => 'workerStop',
        ];
    }

    /**
     * @param ExecEvent $event
     */
    public function beforeExec(ExecEvent $event)
    {
        $this->jobStartedAt = microtime(true);
        $this->command->stdout(date('Y-m-d H:i:s'), Console::FG_YELLOW);
        $class = get_class($event->job);
        $this->command->stdout(" [$event->id] $class (attempt: $event->attempt, pid: $event->workerPid)", Console::FG_GREY);
        $this->command->stdout(' - ', Console::FG_YELLOW);
        $this->command->stdout('Started', Console::FG_GREEN);
        $this->command->stdout(PHP_EOL);
    }

    /**
     * @param ExecEvent $event
     */
    public function afterExec(ExecEvent $event)
    {
        $this->command->stdout(date('Y-m-d H:i:s'), Console::FG_YELLOW);
        $class = get_class($event->job);
        $this->command->stdout(" [$event->id] $class (attempt: $event->attempt, pid: $event->workerPid)", Console::FG_GREY);
        $this->command->stdout(' - ', Console::FG_YELLOW);
        $this->command->stdout('Done', Console::FG_GREEN);
        $duration = number_format(round(microtime(true) - $this->jobStartedAt, 3), 3);
        $this->command->stdout(" ($duration s)", Console::FG_YELLOW);
        $this->command->stdout(PHP_EOL);
    }

    /**
     * @param ErrorEvent $event
     */
    public function afterError(ErrorEvent $event)
    {
        $this->command->stderr(date('Y-m-d H:i:s'), Console::FG_YELLOW);
        $class = get_class($event->job);
        $this->command->stderr(" [$event->id] $class (attempt: $event->attempt, pid: $event->workerPid)", Console::FG_GREY);
        $this->command->stderr(' - ', Console::FG_YELLOW);
        $this->command->stderr('Error', Console::BG_RED);
        $duration = number_format(round(microtime(true) - $this->jobStartedAt, 3), 3);
        $this->command->stderr(" ($duration s)", Console::FG_YELLOW);
        $this->command->stderr(PHP_EOL);
        $this->command->stderr($event->error);
        $this->command->stderr(PHP_EOL);
    }

    /**
     * @param WorkerEvent $event
     * @since 2.0.2
     */
    public function workerStart(WorkerEvent $event)
    {
        $this->workerStartedAt = time();
        $this->command->stdout(date('Y-m-d H:i:s'), Console::FG_YELLOW);
        $this->command->stdout(" [pid: $event->pid]", Console::FG_GREY);
        $this->command->stdout(" - Worker is started\n", Console::FG_GREEN);
    }

    /**
     * @param WorkerEvent $event
     * @since 2.0.2
     */
    public function workerStop(WorkerEvent $event)
    {
        $this->command->stdout(date('Y-m-d H:i:s'), Console::FG_YELLOW);
        $this->command->stdout(" [pid: $event->pid]", Console::FG_GREY);
        $this->command->stdout(' - Worker is stopped ', Console::FG_GREEN);
        $duration = $this->formatDuration(time() - $this->workerStartedAt);
        $this->command->stdout("($duration)\n", Console::FG_YELLOW);
    }

    /**
     * @param int $value
     * @return string
     * @since 2.0.2
     */
    protected function formatDuration($value)
    {
        $seconds = $value % 60;
        $value = ($value - $seconds) / 60;
        $minutes = $value % 60;
        $value = ($value - $minutes) / 60;
        $hours = $value % 24;
        $days = ($value - $hours) / 24;

        if ($days > 0) {
            return sprintf('%d:%02d:%02d:%02d', $days, $hours, $minutes, $seconds);
        } else {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }
    }
}
