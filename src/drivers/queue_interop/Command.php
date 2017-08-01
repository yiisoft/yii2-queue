<?php

namespace yii\queue\queue_interop;

use yii\queue\cli\Command as CliCommand;

class Command extends CliCommand
{
    /**
     * @var Queue
     */
    public $queue;

    /**
     * Listens queue and runs new jobs.
     * It can be used as demon process.
     */
    public function actionListen()
    {
        $this->queue->listen();
    }
}
