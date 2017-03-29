<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\redis;

use zhuravljov\yii\queue\Command as BaseCommand;

/**
 * Manages application redis-queue.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Command extends BaseCommand
{
    /**
     * @var Queue
     */
    public $queue;

    /**
     * @var string
     */
    public $defaultAction = 'stat';

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'stat' => [
                'class' => StatAction::class,
                'queue' => $this->queue,
            ],
        ];
    }

    /**
     * Runs all jobs from redis-queue.
     * It can be used as cron job.
     */
    public function actionRun()
    {
        $this->queue->run();
    }

    /**
     * Listens redis-queue and runs new jobs.
     * It can be used as demon process.
     */
    public function actionListen()
    {
        $this->queue->listen();
    }
}