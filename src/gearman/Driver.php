<?php

namespace zhuravljov\yii\queue\gearman;

use yii\base\BootstrapInterface;
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
        } while (!Signal::isTerm() && $worker->returnCode() === GEARMAN_SUCCESS);
    }
}