<?php

namespace zhuravljov\yii\queue;

use yii\console\Controller;
use yii\helpers\Console;

/**
 * Manages application queue.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Command extends Controller
{
    /**
     * @var Queue
     */
    public $queue;

    /**
     * Runs one job from queue.
     */
    public function actionRunOne()
    {
        if ($this->queue->work(true)) {
            $this->stdout("Job has been run.\n", Console::FG_GREEN);
        } else {
            $this->stdout("Job not found.\n", Console::FG_RED);
        }
    }

    /**
     * Runs all jobs from queue.
     * It can be used as cron job.
     */
    public function actionRunAll()
    {
        $count = 0;
        while ($this->queue->work(false)) {
            $count++;
        }
        $this->stdout("$count jobs has been run.\n", Console::FG_GREEN);
    }

    /**
     * Listens queue and runs new jobs.
     * It can be used as demon process.
     *
     * @param integer $delay Number of seconds for waiting new job.
     */
    public function actionRunLoop($delay = 3)
    {
        while ($this->queue->work(false) || sleep($delay) === 0);
    }

    /**
     * Purges the queue.
     */
    public function actionPurge()
    {
        $this->queue->purge();
    }
}