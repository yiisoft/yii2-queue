<?php

namespace zhuravljov\yii\queue\amqp;

use zhuravljov\yii\queue\Command as BaseCommand;

/**
 * Manages application amqp-queue.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Command extends BaseCommand
{
    /**
     * @var Driver
     */
    public $driver;

    /**
     * Listens amqp-queue and runs new jobs.
     * It can be used as demon process.
     */
    public function actionListen()
    {
        $this->driver->listen();
    }
}