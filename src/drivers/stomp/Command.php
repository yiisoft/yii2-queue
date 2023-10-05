<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue\stomp;

use yii\console\Exception;
use yii\queue\cli\Command as CliCommand;
use yii\queue\cli\Queue as CliQueue;

/**
 * Manages application stomp-queue.
 *
 * @author Sergey Vershinin <versh23@gmail.com>
 * @since 2.3.0
 */
class Command extends CliCommand
{
    /**
     * @var Queue
     */
    public CliQueue $queue;

    /**
     * @inheritdoc
     */
    protected function isWorkerAction($actionID): bool
    {
        return in_array($actionID, ['run', 'listen']);
    }

    /**
     * Runs all jobs from stomp-queue.
     * It can be used as cron job.
     *
     * @return null|int exit code.
     */
    public function actionRun(): ?int
    {
        return $this->queue->run(false);
    }

    /**
     * Listens stomp-queue and runs new jobs.
     * It can be used as daemon process.
     *
     * @param int $timeout number of seconds to wait a job.
     * @throws Exception when params are invalid.
     * @return null|int exit code.
     */
    public function actionListen(int $timeout = 3): ?int
    {
        if ($timeout < 1) {
            throw new Exception('Timeout must be greater that zero.');
        }

        return $this->queue->run(true, $timeout);
    }
}
