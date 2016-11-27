<?php

namespace zhuravljov\yii\queue\db;

use yii\console\Controller;
use yii\helpers\Console;
use zhuravljov\yii\queue\VerboseBehavior;

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
     * Runs all jobs from db-queue.
     * It can be used as cron job.
     */
    public function actionRunAll()
    {
        $this->stdout("Worker has started.\n", Console::FG_GREEN);
        $this->queue->attachBehavior('verbose', VerboseBehavior::class);
        $count = $this->queue->work();
        $this->stdout("$count jobs have been run.\n", Console::FG_GREEN);
    }

    /**
     * Listens db-queue and runs new jobs.
     * It can be used as demon process.
     *
     * @param integer $delay Number of seconds for waiting new job.
     */
    public function actionRunLoop($delay = 3)
    {
        $this->stdout(date('Y-m-d H:i:s') . ": worker has started.\n", Console::FG_GREEN);
        $this->queue->attachBehavior('verbose', VerboseBehavior::class);
        do {
            $this->queue->work();
        } while (!$delay || sleep($delay) === 0);
    }

    /**
     * Purges the db-queue.
     */
    public function actionPurge()
    {
        $this->queue->purge();
    }
}