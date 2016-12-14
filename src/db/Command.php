<?php

namespace zhuravljov\yii\queue\db;

use zhuravljov\yii\queue\Command as BaseCommand;

/**
 * Manages application db-queue.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Command extends BaseCommand
{
    /**
     * @var Driver
     */
    public $driver;

    /**
     * Runs all jobs from db-queue.
     * It can be used as cron job.
     */
    public function actionRun()
    {
        $this->driver->run();
    }

    /**
     * Listens db-queue and runs new jobs.
     * It can be used as demon process.
     *
     * @param integer $delay Number of seconds for waiting new job.
     */
    public function actionListen($delay = 3)
    {
        $this->driver->listen($delay);
    }
}