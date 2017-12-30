<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\gearman;

use yii\queue\cli\Command as CliCommand;

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
    public $queue;


    /**
     * @inheritdoc
     */
    protected function isWorkerAction($actionID)
    {
        return in_array($actionID, ['run' ,'listen'], true);
    }

    /**
     * Runs all jobs from gearman-queue.
     * It can be used as cron job.
     *
     * @return null|int exit code.
     */
    public function actionRun()
    {
        return $this->queue->run(false);
    }

    /**
     * Listens gearman-queue and runs new jobs.
     * It can be used as daemon process.
     *
     * @return null|int exit code.
     */
    public function actionListen()
    {
        return $this->queue->run(true);
    }
}
