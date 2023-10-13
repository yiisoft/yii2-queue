<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue\sqs;

use yii\console\Exception;
use yii\queue\cli\Command as CliCommand;
use yii\queue\cli\Queue as CliQueue;

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
    public CliQueue $queue;

    /**
     * Runs all jobs from sqs.
     * It can be used as cron job.
     *
     * @return null|int exit code.
     */
    public function actionRun(): ?int
    {
        return $this->queue->run(false);
    }

    /**
     * Listens sqs and runs new jobs.
     * It can be used as demon process.
     *
     * @param int $timeout number of seconds to sleep before next reading of the queue.
     * @return null|int exit code.
     * @throws Exception when params are invalid.
     */
    public function actionListen(int $timeout = 3): ?int
    {
        if ($timeout < 1 || $timeout > 20) {
            throw new Exception('Timeout must be between 1 and 20');
        }

        return $this->queue->run(true, $timeout);
    }

    /**
     * Clears the queue.
     */
    public function actionClear(): void
    {
        if ($this->confirm('Are you sure?')) {
            $this->queue->clear();
            $this->stdout("Queue has been cleared.\n");
        }
    }

    /**
     * @inheritdoc
     */
    protected function isWorkerAction($actionID): bool
    {
        return in_array($actionID, ['run', 'listen']);
    }
}
