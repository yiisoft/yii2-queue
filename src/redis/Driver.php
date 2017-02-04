<?php

namespace zhuravljov\yii\queue\redis;

use yii\base\BootstrapInterface;
use yii\di\Instance;
use yii\helpers\Inflector;
use yii\redis\Connection;
use zhuravljov\yii\queue\Driver as BaseDriver;
use zhuravljov\yii\queue\Signal;

/**
 * Redis Driver
 *
 * @property integer $reservedCount
 * @property array $workersInfo
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Driver extends BaseDriver implements BootstrapInterface
{
    /**
     * @var Connection|array|string
     */
    public $redis = 'redis';
    /**
     * @var string
     */
    public $channel = 'queue';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->redis = Instance::ensure($this->redis, Connection::class);
    }

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
        $this->redis->executeCommand('RPUSH', ["$this->channel.reserved", $this->serialize($job)]);
    }

    /**
     * Runs all jobs from redis-queue.
     */
    public function run()
    {
        $this->openWorker();
        while (($result = $this->redis->executeCommand('LPOP', ["$this->channel.reserved"])) !== null) {
            $job = $this->unserialize($result);
            $this->getQueue()->run($job);
        }
        $this->closeWorker();
    }

    /**
     * Listens redis-queue and runs new jobs.
     */
    public function listen()
    {
        $this->openWorker();
        while (!Signal::isTerm()) {
            if ($result = $this->redis->executeCommand('BLPOP', ["$this->channel.reserved", 10])) {
                $job = $this->unserialize($result[1]);
                $this->getQueue()->run($job);
            }
        }
        $this->closeWorker();
    }

    /**
     * @return integer
     */
    public function getReservedCount()
    {
        return $this->redis->executeCommand('LLEN', ["$this->channel.reserved"]);
    }

    /**
     * @return array
     */
    public function getWorkersInfo()
    {
        $workers = [];
        $data = $this->redis->executeCommand('CLIENT', ['LIST']);
        foreach (explode("\n", trim($data)) as $line) {
            $client = [];
            foreach (explode(' ', trim($line)) as $pair) {
                list($key, $value) = explode('=', $pair, 2);
                $client[$key] = $value;
            }
            if (isset($client['name']) && strpos($client['name'], "$this->channel.worker")  === 0) {
                $workers[$client['name']] = $client;
            }
        }

        return $workers;
    }

    protected function openWorker()
    {
        $id = $this->redis->executeCommand('INCR', ["$this->channel.last_worker_id"]);
        $this->redis->executeCommand('CLIENT', ['SETNAME', "$this->channel.worker.$id"]);
    }

    protected function closeWorker()
    {
        $this->redis->executeCommand('CLIENT', ['SETNAME', '']);
    }
}