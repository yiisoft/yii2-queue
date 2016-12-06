<?php

namespace zhuravljov\yii\queue\redis;

use yii\console\Controller;
use zhuravljov\yii\queue\VerboseBehavior;

/**
 * Manages application redis-queue.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Command extends Controller
{
    /**
     * @var Driver
     */
    public $driver;

    /**
     * Runs all jobs from redis-queue.
     * It can be used as cron job.
     */
    public function actionRun()
    {
        $this->driver->queue->attachBehavior('verbose', VerboseBehavior::class);
        $this->driver->run();
    }

    /**
     * Listens redis-queue and runs new jobs.
     * It can be used as demon process.
     *
     * @param integer $delay Number of seconds for waiting new job.
     */
    public function actionListen($delay = 3)
    {
        $this->driver->queue->attachBehavior('verbose', VerboseBehavior::class);
        do {
            $this->driver->run();
        } while (!$delay || sleep($delay) === 0);
    }
}