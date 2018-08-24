<?php

namespace yii\queue\stomp;

use yii\queue\cli\Command as CliCommand;
use yii\console\Exception;

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
        return in_array($actionID, ['run', 'listen']);
    }


    public function actionRun()
    {
        return $this->queue->run(false);
    }


    public function actionListen($timeout = 3)
    {
        if (!is_numeric($timeout)) {
            throw new Exception('Timeout must be numeric.');
        }
        if ($timeout < 1) {
            throw new Exception('Timeout must be greater that zero.');
        }

        return $this->queue->run(true, $timeout);
    }
}
