<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\gearman;

use zhuravljov\yii\queue\Command as BaseCommand;

/**
 * Manages application gearman-queue.
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
     * Listens gearman-queue and runs new jobs.
     * It can be used as demon process.
     */
    public function actionListen()
    {
        $this->queue->listen();
    }
}