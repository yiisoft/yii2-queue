<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\gearman;

use yii\base\BootstrapInterface;
use yii\base\NotSupportedException;
use yii\helpers\Inflector;
use zhuravljov\yii\queue\Driver as BaseDriver;
use zhuravljov\yii\queue\Signal;

/**
 * Gearman Driver
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Driver extends BaseDriver implements BootstrapInterface
{
    public $host = 'localhost';
    public $port = 4730;
    public $channel = 'queue';

    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        if ($app instanceof \yii\console\Application) {
            $app->controllerMap[Inflector::camel2id($this->queue->id)] = [
                'class' => Command::class,
                'driver' => $this,
            ];
        }
    }

    /**
     * @inheritdoc
     */
    public function push($job)
    {
        $client = new \GearmanClient();
        $client->addServer($this->host, $this->port);
        $client->doBackground($this->channel, $this->serialize($job));
    }

    /**
     * @inheritdoc
     */
    public function later($job, $timeout)
    {
        throw new NotSupportedException('Delayed work is not supported in the driver.');
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
            $job = $this->unserialize($message->workload());
            $this->getQueue()->run($job);
        });

        do {
            $worker->work();
        } while (!Signal::isExit() && $worker->returnCode() === GEARMAN_SUCCESS);
    }
}