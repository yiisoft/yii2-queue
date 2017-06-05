<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\drivers\dispatcher;

use yii\helpers\Console;
use zhuravljov\yii\queue\cli\Command as CliCommand;

/**
 * Manages application file-queue.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Command extends CliCommand
{
    /**
     * @var Queue
     */
    public $queue;

    /**
     * Runs all jobs from file-queue.
     * It can be used as cron job.
     */
    public function actionRun()
    {
        $this->queue->run();
    }

    /**
     * Listens file-queue and runs new jobs.
     * It can be used as demon process.
     */
    public function actionListen()
    {
        $args = func_get_args();
        call_user_func_array([$this->queue, 'listen'], $args);
    }

    /**
     * Queues info
     */
    public function actionInfo()
    {
        foreach ($this->queue->group as $key => $queue) {
            if ($queue->commandClass) {
                /* @var $command \zhuravljov\yii\queue\cli\Command */
                $command = \Yii::createObject([
                        'class' => $queue->commandClass,
                        'queue' => $queue,
                    ] + $queue->commandOptions, ["queue-$key", $this->module]);

                if ($action = $command->createAction('info')) {
                    Console::output($command->ansiFormat("Queue Index{$key}", Console::FG_BLUE));
                    $command->run('info');
                }
            }
        }
    }
}