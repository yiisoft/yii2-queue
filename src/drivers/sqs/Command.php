<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\sqs;

use yii\console\Exception;
use yii\queue\cli\Command as CliCommand;

/**
 * Manages application aws sqs-queue.
 *
 * @author Max Kozlovsky <kozlovskymaxim@gmail.com>
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
     * @throws Exception when params are invalid.
     * @return null|int exit code.
     */
    public function actionListen($timeout = 3)
    {
        if (!is_numeric($timeout)) {
            throw new Exception('Timeout must be numeric.');
        }
        if ($timeout < 1 || $timeout > 20) {
            throw new Exception('Timeout must be between 1 and 20');
        }

        return $this->queue->run(true, $timeout);
    }

    /**
     * Clears the queue.
     */
    public function actionClear()
    {
        if ($this->confirm('Are you sure?')) {
            $this->queue->clear();
            $this->stdout("Queue has been cleared.\n");
        }
    }

    /**
     * @inheritdoc
     */
    protected function isWorkerAction($actionID)
    {
        return in_array($actionID, ['run', 'listen']);
    }
}
