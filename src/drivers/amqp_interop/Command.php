<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue\amqp_interop;

use yii\queue\cli\Command as CliCommand;

/**
 * Manages application amqp-queue.
 *
 * @author Maksym Kotliar <kotlyar.maksim@gmail.com>
 * @since 2.0.2
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
