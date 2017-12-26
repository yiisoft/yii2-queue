<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\sqs;

use yii\queue\cli\Command as CliCommand;

/**
 * Manages application aws sqs-queue.
 *
 * @author Manoj Malviya <manojm@girnarsoft.com>
 */
class Command extends CliCommand
{
    /**
     * @var Queue
     */
    public $queue;

    /**
     * Runs all jobs from sqs.
     * It can be used as cron job.
     *
     * @return null|int exit code.
     */
    public function actionRun()
    {
        return $this->queue->run(false);
    }

    /**
     * Listens sqs and runs new jobs.
     * It can be used as demon process.
     *
     * @param int $timeout number of seconds to sleep before next reading of the queue.
     * @return null|int exit code.
     */
    public function actionListen($timeout = 3)
    {
        return $this->queue->run(true, $timeout);
    }

    /**
     * @inheritdoc
     */
    protected function isWorkerAction($actionID)
    {
        return in_array($actionID, ['run' ,'listen']);
    }
}