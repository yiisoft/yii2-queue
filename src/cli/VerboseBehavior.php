<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue\cli;

use yii\base\Behavior;
use yii\base\Component;
use yii\console\Controller;
use yii\helpers\BaseConsole;
use yii\helpers\Console;
use yii\queue\ExecEvent;
use yii\queue\JobInterface;

/**
 * Verbose Behavior.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class VerboseBehavior extends Behavior
{
    /**
     * @var Queue|null|Component
     */
    public $owner;
    /**
     * @var Controller
     * @psalm-suppress PropertyNotSetInConstructor
     */
    public Controller $command;

    /**
     * @var float|null timestamp
     */
    private ?float $jobStartedAt = null;
    /**
     * @var int|null timestamp
     */
    private ?int $workerStartedAt = null;

    /**
     * @inheritdoc
     */
    public function events(): array
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
    public function beforeExec(ExecEvent $event): void
    {
        $this->jobStartedAt = microtime(true);
        Console::ansiFormat(date('Y-m-d H:i:s'), [BaseConsole::FG_YELLOW]);
        Console::ansiFormat($this->jobTitle($event), [BaseConsole::FG_GREY]);
        Console::ansiFormat(' - ', [BaseConsole::FG_YELLOW]);
        Console::ansiFormat('Started', [BaseConsole::FG_GREEN]);
        $this->command->stdout(PHP_EOL);
    }

    /**
     * @param ExecEvent $event
     */
    public function afterExec(ExecEvent $event): void
    {
        $this->command->stdout(date('Y-m-d H:i:s'), BaseConsole::FG_YELLOW);
        $this->command->stdout($this->jobTitle($event), BaseConsole::FG_GREY);
        $this->command->stdout(' - ', BaseConsole::FG_YELLOW);
        $this->command->stdout('Done', BaseConsole::FG_GREEN);
        $duration = number_format(round(microtime(true) - $this->jobStartedAt, 3), 3);
        $memory = round(memory_get_peak_usage()/1024/1024, 2);
        $this->command->stdout(" ($duration s, $memory MiB)", BaseConsole::FG_YELLOW);
        $this->command->stdout(PHP_EOL);
    }

    /**
     * @param ExecEvent $event
     */
    public function afterError(ExecEvent $event): void
    {
        $this->command->stdout(date('Y-m-d H:i:s'), BaseConsole::FG_YELLOW);
        $this->command->stdout($this->jobTitle($event), BaseConsole::FG_GREY);
        $this->command->stdout(' - ', BaseConsole::FG_YELLOW);
        $this->command->stdout('Error', BaseConsole::BG_RED);
        if ($this->jobStartedAt) {
            $duration = number_format(round(microtime(true) - $this->jobStartedAt, 3), 3);
            $this->command->stdout(" ($duration s)", BaseConsole::FG_YELLOW);
        }
        if (null !== $event->error) {
            $this->command->stdout(PHP_EOL);
            $this->command->stdout('> ' . get_class($event->error) . ': ', BaseConsole::FG_RED);
            $message = explode("\n", ltrim($event->error->getMessage()), 2)[0]; // First line
            $this->command->stdout($message, BaseConsole::FG_GREY);
            $this->command->stdout(PHP_EOL);
            $this->command->stdout('Stack trace:', BaseConsole::FG_GREY);
            $this->command->stdout(PHP_EOL);
            $this->command->stdout($event->error->getTraceAsString(), BaseConsole::FG_GREY);
            $this->command->stdout(PHP_EOL);
        }
    }

    /**
     * @param ExecEvent $event
     * @return string
     * @since 2.0.2
     */
    protected function jobTitle(ExecEvent $event): string
    {
        $name = $event->job instanceof JobInterface ? get_class($event->job) : 'unknown job';
        $extra = "attempt: $event->attempt";
        if ($pid = $event->sender?->getWorkerPid()) {
            $extra .= ", pid: $pid";
        }
        return " [$event->id] $name ($extra)";
    }

    /**
     * @param WorkerEvent $event
     * @since 2.0.2
     */
    public function workerStart(WorkerEvent $event): void
    {
        $this->workerStartedAt = time();
        $this->command->stdout(date('Y-m-d H:i:s'), BaseConsole::FG_YELLOW);
        $pid = $event->sender->getWorkerPid();
        $this->command->stdout(" [pid: $pid]", BaseConsole::FG_GREY);
        $this->command->stdout(" - Worker is started\n", BaseConsole::FG_GREEN);
    }

    /**
     * @param WorkerEvent $event
     * @since 2.0.2
     */
    public function workerStop(WorkerEvent $event): void
    {
        $this->command->stdout(date('Y-m-d H:i:s'), BaseConsole::FG_YELLOW);
        $pid = $event->sender->getWorkerPid();
        $this->command->stdout(" [pid: $pid]", BaseConsole::FG_GREY);
        $this->command->stdout(' - Worker is stopped ', BaseConsole::FG_GREEN);
        $duration = $this->formatDuration(time() - $this->workerStartedAt);
        $this->command->stdout("($duration)\n", BaseConsole::FG_YELLOW);
    }

    /**
     * @param int $value
     * @return string
     * @since 2.0.2
     */
    protected function formatDuration(int $value): string
    {
        $seconds = $value % 60;
        $value = ($value - $seconds) / 60;
        $minutes = $value % 60;
        $value = ($value - $minutes) / 60;
        $hours = $value % 24;
        $days = ($value - $hours) / 24;

        if ($days > 0) {
            return sprintf('%d:%02d:%02d:%02d', $days, $hours, $minutes, $seconds);
        }

        return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
    }
}
