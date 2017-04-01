<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\gearman;

use yii\base\BootstrapInterface;
use yii\base\NotSupportedException;
use yii\console\Application as ConsoleApp;
use zhuravljov\yii\queue\Queue as BaseQueue;
use zhuravljov\yii\queue\Signal;

/**
 * Gearman Queue
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Queue extends BaseQueue implements BootstrapInterface
{
    public $host = 'localhost';
    public $port = 4730;
    public $channel = 'queue';

    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        if ($app instanceof ConsoleApp) {
            $app->controllerMap[$this->getId()] = [
                'class' => Command::class,
                'queue' => $this,
            ];
        }
    }

    /**
     * Listens gearman-queue and runs new jobs.
     */
    public function listen()
    {
        $worker = new \GearmanWorker();
        $worker->addServer($this->host, $this->port);
        $worker->setTimeout(-1);
        $worker->addFunction($this->channel, function (\GearmanJob $message) {
            $this->handleMessage($message->workload());
        });

        do {
            $worker->work();
        } while (!Signal::isExit() && $worker->returnCode() === GEARMAN_SUCCESS);
    }

    /**
     * @inheritdoc
     */
    protected function sendMessage($message, $timeout)
    {
        if ($timeout) {
            throw new NotSupportedException('Delayed work is not supported in the driver.');
        }

        $client = new \GearmanClient();
        $client->addServer($this->host, $this->port);
        $client->doBackground($this->channel, $message);
    }
}