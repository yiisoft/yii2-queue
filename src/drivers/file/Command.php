<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\file;

use yii\console\ExitCode;
use yii\queue\cli\Command as CliCommand;

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
     * @inheritdoc
     */
    protected function isWorkerAction($actionID)
    {
        return in_array($actionID, ['run' ,'listen']);
    }

    /**
     * Runs all jobs from file-queue.
     * It can be used as cron job.
     */
    public function actionRun()
    {
        $this->queue->run(false);
    }

    /**
     * Listens file-queue and runs new jobs.
     * It can be used as demon process.
     *
     * @param int $timeout number of seconds to sleep before next reading of the queue.
     */
    public function actionListen($timeout = 3)
    {
        $this->queue->run(true, $timeout);
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
        if ($this->queue->remove((int) $id)) {
            $this->stdout("The job has been removed.\n");
            return ExitCode::OK;
        } else {
            $this->stdout("The job was not found.\n");
            return ExitCode::DATAERR;
        }
    }
}
