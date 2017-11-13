<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\redis;

use yii\console\ExitCode;
use yii\queue\cli\Command as CliCommand;

/**
 * Manages application redis-queue.
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
     * @var string
     */
    public $defaultAction = 'info';


    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'info' => InfoAction::class,
        ];
    }

    /**
     * Runs all jobs from redis-queue.
     * It can be used as cron job.
     */
    public function actionRun()
    {
        $this->queue->run();
    }

    /**
     * Listens redis-queue and runs new jobs.
     * It can be used as demon process.
     *
     * @param int $wait timeout
     */
    public function actionListen($wait = 3)
    {
        $this->queue->listen($wait);
    }

    /**
     * Clears the queue.
     *
     * @since 2.0.1
     */
    public function actionClear()
    {
        if ($this->confirm('Are you sure?')) {
            $this->queue->clear();
            $this->stdout("Queue has been cleared.\n");
        }
    }

    /**
     * Removes a job by id.
     *
     * @param int $id
     * @return int exit code
     * @since 2.0.1
     */
    public function actionRemove($id)
    {
        if ($this->queue->remove($id)) {
            $this->stdout("The job has been removed.\n");
            return ExitCode::OK;
        } else {
            $this->stdout("The job was not found.\n");
            return ExitCode::DATAERR;
        }
    }
}