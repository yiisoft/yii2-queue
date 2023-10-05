<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue\cli;

use Yii;
use yii\base\BootstrapInterface;
use yii\base\InvalidConfigException;
use yii\console\Application as ConsoleApp;
use yii\helpers\Inflector;
use yii\queue\Queue as BaseQueue;

/**
 * Queue with CLI.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class Queue extends BaseQueue implements BootstrapInterface
{
    /**
     * @event WorkerEvent that is triggered when the worker is started.
     * @since 2.0.2
     */
    public const EVENT_WORKER_START = 'workerStart';
    /**
     * @event WorkerEvent that is triggered each iteration between requests to queue.
     * @since 2.0.3
     */
    public const EVENT_WORKER_LOOP = 'workerLoop';
    /**
     * @event WorkerEvent that is triggered when the worker is stopped.
     * @since 2.0.2
     */
    public const EVENT_WORKER_STOP = 'workerStop';

    /**
     * @var array|string
     * @since 2.0.2
     */
    public string|array $loopConfig = SignalLoop::class;
    /**
     * @var string command class name
     */
    public string $commandClass = Command::class;
    /**
     * @var array of additional options of command
     */
    public array $commandOptions = [];
    /**
     * @var callable|null
     * @internal for worker command only
     */
    public $messageHandler;

    /**
     * @var int|null current process ID of a worker.
     * @since 2.0.2
     */
    private ?int $_workerPid = null;

    /**
     * @return string command id
     */
    protected function getCommandId(): string
    {
        /** @psalm-suppress UndefinedClass */
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
    public function bootstrap($app): void
    {
        if ($app instanceof ConsoleApp) {
            $app->controllerMap[$this->getCommandId()] = [
                'class' => $this->commandClass,
                'queue' => $this,
            ] + $this->commandOptions;
        }
    }

    /**
     * Runs worker.
     *
     * @param callable $handler
     * @return null|int exit code
     * @since 2.0.2
     */
    protected function runWorker(callable $handler): ?int
    {
        $this->_workerPid = getmypid();
        /** @var LoopInterface $loop */
        /** @psalm-suppress UndefinedClass */
        $loop = Yii::createObject($this->loopConfig, [$this]);

        $event = new WorkerEvent(['loop' => $loop]);
        $this->trigger(self::EVENT_WORKER_START, $event);
        if ($event->exitCode !== null) {
            return $event->exitCode;
        }

        try {
            $handler(function () use ($loop, $event) {
                $this->trigger(self::EVENT_WORKER_LOOP, $event);
                return $event->exitCode === null && $loop->canContinue();
            });
        } finally {
            $this->trigger(self::EVENT_WORKER_STOP, $event);
            $this->_workerPid = null;
        }

        return $event->exitCode;
    }

    /**
     * Gets process ID of a worker.
     *
     * @inheritdoc
     * @return int|null
     * @since 2.0.2
     */
    public function getWorkerPid(): ?int
    {
        return $this->_workerPid;
    }

    /**
     * @inheritdoc
     */
    protected function handleMessage(int|string $id, string $message, int $ttr, int $attempt): bool
    {
        if ($this->messageHandler) {
            return call_user_func($this->messageHandler, $id, $message, $ttr, $attempt);
        }

        return parent::handleMessage($id, $message, $ttr, $attempt);
    }

    /**
     * @param string $id of a message
     * @param string $message
     * @param int $ttr time to reserve
     * @param int $attempt number
     * @param int|null $workerPid of worker process
     * @return bool
     * @internal for worker command only
     */
    public function execute(string $id, string $message, int $ttr, int $attempt, ?int $workerPid): bool
    {
        $this->_workerPid = $workerPid;
        return parent::handleMessage($id, $message, $ttr, $attempt);
    }
}
