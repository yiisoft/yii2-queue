<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue\gearman;

use yii\queue\cli\Command as CliCommand;
use yii\queue\cli\Queue as CliQueue;

/**
 * Manages application gearman-queue.
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
     * @inheritdoc
     */
    protected function isWorkerAction($actionID): bool
    {
        return in_array($actionID, ['run', 'listen'], true);
    }

    /**
     * Runs all jobs from gearman-queue.
     * It can be used as cron job.
     *
     * @return null|int exit code.
     */
    public function actionRun(): ?int
    {
        return $this->queue->run(false);
    }

    /**
     * Listens gearman-queue and runs new jobs.
     * It can be used as daemon process.
     *
     * @return null|int exit code.
     */
    public function actionListen(): ?int
    {
        return $this->queue->run(true);
    }
}
