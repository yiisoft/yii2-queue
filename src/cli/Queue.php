<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\cli;

use Yii;
use yii\base\BootstrapInterface;
use yii\base\InvalidConfigException;
use yii\console\Application as ConsoleApp;
use yii\di\Instance;
use yii\helpers\Inflector;
use yii\queue\Queue as BaseQueue;

/**
 * Queue with CLI
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class Queue extends BaseQueue implements BootstrapInterface
{
    /**
     * @event WorkerEvent
     * @since 2.0.2
     */
    const EVENT_WORKER_START = 'workerStart';
    /**
     * @event WorkerEvent
     * @since 2.0.2
     */
    const EVENT_WORKER_STOP = 'workerStop';
    /**
     * @var LoopInterface|array|string
     * @since 2.0.2
     */
    public $loop = SignalLoop::class;
    /**
     * @var string command class name
     */
    public $commandClass = Command::class;
    /**
     * @var array of additional options of command
     */
    public $commandOptions = [];
    /**
     * @var callable|null
     * @internal only for command
     */
    public $messageHandler;
    /**
     * @var int current process ID of a worker.
     * @since 2.0.2
     */
    private $_workerPid;

    /**
     * @return string command id
     * @throws
     */
    protected function getCommandId()
    {
        foreach (Yii::$app->getComponents(false) as $id => $component) {
            if ($component === $this) {
                return Inflector::camel2id($id);
            }
        }
        throw new InvalidConfigException('Queue must be an application component.');
    }

    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        if ($app instanceof ConsoleApp) {
            $app->controllerMap[$this->getCommandId()] = [
                'class' => $this->commandClass,
                'queue' => $this,
            ] + $this->commandOptions;
        }
    }

    /**
     * @param \yii\base\Action $action
     * @param int $workerPid
     * @internal for worker command only.
     * @since 2.0.2
     */
    public function onWorkerStart($action, $workerPid)
    {
        $this->loop = Instance::ensure($this->loop, LoopInterface::class);

        $this->_workerPid = $workerPid;
        $this->trigger(self::EVENT_WORKER_START, new WorkerEvent([
            'action' => $action,
            'pid' => $workerPid,
        ]));
    }

    /**
     * @param \yii\base\Action $action
     * @param int $workerPid
     * @internal for worker command only.
     * @since 2.0.2
     */
    public function onWorkerStop($action, $workerPid)
    {
        $this->trigger(self::EVENT_WORKER_STOP, new WorkerEvent([
            'action' => $action,
            'pid' => $workerPid,
        ]));
    }

    /**
     * Gets process ID of a worker.
     *
     * @inheritdoc
     * @return int
     * @since 2.0.2
     */
    protected function getWorkerPid()
    {
        return $this->_workerPid;
    }

    /**
     * @inheritdoc
     */
    protected function handleMessage($id, $message, $ttr, $attempt)
    {
        if ($this->messageHandler) {
            return call_user_func($this->messageHandler, $id, $message, $ttr, $attempt);
        } else {
            return parent::handleMessage($id, $message, $ttr, $attempt);
        }
    }

    /**
     * @param string $id of a message
     * @param string $message
     * @param int $ttr time to reserve
     * @param int $attempt number
     * @param int $workerPid of worker process
     * @return bool
     * @internal only for command
     */
    public function execute($id, $message, $ttr, $attempt, $workerPid)
    {
        $this->_workerPid = $workerPid;
        return parent::handleMessage($id, $message, $ttr, $attempt);
    }
}
