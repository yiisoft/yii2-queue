<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\amqp;

use yii\queue\cli\Command as CliCommand;

/**
 * Manages application amqp-queue.
 *
 * @deprecated since 2.0.2 and will be removed in 3.0. Consider using amqp_interop driver instead
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
        return $actionID === 'listen';
    }

    /**
     * Listens amqp-queue and runs new jobs.
     * It can be used as daemon process.
     */
    public function actionListen()
    {
        $this->queue->listen();
    }
}
