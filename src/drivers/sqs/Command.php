<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\sqs;

use yii\queue\cli\Command as CliCommand;

/**
 * Manages application aws sqs-queue.
 *
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
     */
    public function actionRun()
    {
        $this->queue->run();
    }

    /**
     * Listens sqs and runs new jobs.
     * It can be used as demon process.
     */
    public function actionListen()
    {
        $this->queue->listen();
    }
}