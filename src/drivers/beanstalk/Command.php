<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\beanstalk;

use yii\helpers\Console;
use yii\queue\cli\Command as CliCommand;

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
    public $queue;

    /**
     * @var string
     */
    public $defaultAction = 'info';

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'info' => InfoAction::class,
        ];
    }

    /**
     * Runs all jobs from beanstalk-queue.
     * It can be used as cron job.
     */
    public function actionRun()
    {
        $this->queue->run();
    }

    /**
     * Listens beanstalk-queue and runs new jobs.
     * It can be used as demon process.
     */
    public function actionListen()
    {
        $this->queue->listen();
    }

    /**
     * Removes a job by id.
     *
     * @param int $id
     * @return int exit code
     */
    public function actionRemove($id)
    {
        if ($this->queue->remove($id)) {
            Console::output('The job has been removed.');
            return static::EXIT_CODE_NORMAL;
        } else {
            Console::output('The job is not found.');
            return static::EXIT_CODE_ERROR;
        }
    }
}