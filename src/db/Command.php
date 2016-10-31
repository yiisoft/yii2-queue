<?php

namespace zhuravljov\yii\queue\db;

use yii\console\Controller;
use yii\helpers\Console;

/**
 * Manages application db-queue.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Command extends Controller
{
    /**
     * @var \zhuravljov\yii\queue\Queue
     */
    public $queue;

    /**
     * Runs one job from db-queue.
     */
    public function actionRunOne()
    {
        if ($this->queue->work(true)) {
            $this->stdout("Job has been complete.\n", Console::FG_GREEN);
        } else {
            $this->stdout("Job not found.\n", Console::FG_RED);
        }
    }

    /**
     * Runs all jobs from db-queue.
     * It can be used as cron job.
     */
    public function actionRunAll()
    {
        $this->stdout("Worker has been started.\n", Console::FG_GREEN);
        $count = 0;
        while ($this->queue->work(false)) {
            $count++;
        }
        $this->stdout("$count jobs have been complete.\n", Console::FG_GREEN);
    }

    /**
     * Listens db-queue and runs new jobs.
     * It can be used as demon process.
     *
     * @param integer $delay Number of seconds for waiting new job.
     */
    public function actionRunLoop($delay = 3)
    {
        $this->stdout("Worker has been started.\n", Console::FG_GREEN);
        while (($run = $this->queue->work(false)) || !$delay || sleep($delay) === 0) {
            if ($run) {
                $this->stdout("Job has been complete.\n", Console::FG_GREEN);
            }
        }
    }

    /**
     * Purges the db-queue.
     */
    public function actionPurge()
    {
        $this->queue->purge();
    }
}