<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue\beanstalk;

use yii\console\Exception;
use yii\queue\cli\Command as CliCommand;
use yii\queue\cli\Queue as CliQueue;

/**
 * Manages application beanstalk-queue.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Command extends CliCommand
{
    /**
     * @var Queue
     */
    public CliQueue $queue;
    /**
     * @var string
     */
    public $defaultAction = 'info';

    /**
     * @inheritdoc
     */
    public function actions(): array
    {
        return [
            'info' => InfoAction::class,
        ];
    }

    /**
     * @inheritdoc
     */
    protected function isWorkerAction($actionID): bool
    {
        return in_array($actionID, ['run', 'listen']);
    }

    /**
     * Runs all jobs from beanstalk-queue.
     * It can be used as cron job.
     *
     * @return null|int exit code.
     */
    public function actionRun(): ?int
    {
        return $this->queue->run(false);
    }

    /**
     * Listens beanstalk-queue and runs new jobs.
     * It can be used as daemon process.
     *
     * @param int $timeout number of seconds to wait a job.
     * @return null|int exit code.
     *@throws Exception when params are invalid.
     */
    public function actionListen(int $timeout = 3): ?int
    {
        if ($timeout < 1) {
            throw new Exception('Timeout must be greater than zero.');
        }

        return $this->queue->run(true, $timeout);
    }

    /**
     * Removes a job by id.
     *
     * @param int $id of the job.
     * @throws Exception when the job is not found.
     * @since 2.0.1
     */
    public function actionRemove(int $id): void
    {
        if (!$this->queue->remove($id)) {
            throw new Exception('The job is not found.');
        }
    }
}
