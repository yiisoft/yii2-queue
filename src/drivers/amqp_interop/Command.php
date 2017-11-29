<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\amqp_interop;

use yii\queue\cli\Command as CliCommand;

/**
 * Manages application amqp-queue.
 *
 * @author Maksym Kotliar <kotlyar.maksim@gmail.com>
 */
class Command extends CliCommand
{
    /**
     * @var Queue
     */
    public $queue;

    /**
     * Listens amqp-queue and runs new jobs.
     * It can be used as demon process.
     */
    public function actionListen()
    {
        $this->queue->listen();
    }

    /**
     * Creates all required queues, topics etc
     */
    public function actionSetupBroker()
    {
        $this->queue->setupBroker();
    }
}