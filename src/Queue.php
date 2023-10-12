<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue;

use yii\base\Component;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\di\Instance;
use yii\helpers\VarDumper;
use yii\queue\serializers\PhpSerializer;
use yii\queue\serializers\SerializerInterface;

/**
 * Base Queue.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class Queue extends Component
{
    /**
     * @event PushEvent
     */
    public const EVENT_BEFORE_PUSH = 'beforePush';
    /**
     * @event PushEvent
     */
    public const EVENT_AFTER_PUSH = 'afterPush';
    /**
     * @event ExecEvent
     */
    public const EVENT_BEFORE_EXEC = 'beforeExec';
    /**
     * @event ExecEvent
     */
    public const EVENT_AFTER_EXEC = 'afterExec';
    /**
     * @event ExecEvent
     */
    public const EVENT_AFTER_ERROR = 'afterError';
    /**
     * @see Queue::isWaiting()
     */
    public const STATUS_WAITING = 1;
    /**
     * @see Queue::isReserved()
     */
    public const STATUS_RESERVED = 2;
    /**
     * @see Queue::isDone()
     */
    public const STATUS_DONE = 3;

    /**
     * @var bool whether to enable strict job type control.
     * Note that in order to enable type control, a pushing job must be [[JobInterface]] instance.
     * @since 2.0.1
     */
    public bool $strictJobType = true;
    /**
     * @var SerializerInterface|array|string
     */
    public string|array|SerializerInterface $serializer = PhpSerializer::class;
    /**
     * @var int default time to reserve a job
     */
    public int $ttr = 300;
    /**
     * @var int default attempt count
     */
    public int $attempts = 1;

    private ?int $pushTtr = null;
    private ?int $pushDelay = null;
    private int|string|null $pushPriority = null;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $this->serializer = Instance::ensure($this->serializer, SerializerInterface::class);

        if ($this->ttr <= 0) {
            throw new InvalidConfigException('Default TTR must be greater that zero.');
        }

        if ($this->attempts <= 0) {
            throw new InvalidConfigException('Default attempts count must be greater that zero.');
        }
    }

    /**
     * Sets TTR for job execute.
     *
     * @param int|null $value
     * @return $this
     */
    public function ttr(?int $value): static
    {
        $this->pushTtr = $value;
        return $this;
    }

    /**
     * Sets delay for later execute.
     *
     * @param int|null $value
     * @return $this
     */
    public function delay(?int $value): static
    {
        $this->pushDelay = $value;
        return $this;
    }

    /**
     * Sets job priority.
     *
     * @param int|string|null $value
     * @return $this
     */
    public function priority(int|string|null $value): static
    {
        $this->pushPriority = $value;
        return $this;
    }

    /**
     * Pushes job into queue.
     *
     * @param JobInterface|mixed $job
     * @return int|string|null id of a job message
     */
    public function push($job): int|string|null
    {
        $event = new PushEvent([
            'job' => $job,
            'ttr' => $this->pushTtr ?: (
                $job instanceof RetryableJobInterface
                    ? $job->getTtr()
                    : $this->ttr
            ),
            'delay' => $this->pushDelay ?: 0,
            'priority' => $this->pushPriority,
        ]);
        $this->pushTtr = null;
        $this->pushDelay = null;
        $this->pushPriority = null;

        $this->trigger(self::EVENT_BEFORE_PUSH, $event);
        if ($event->handled) {
            return null;
        }

        if ($this->strictJobType && !($event->job instanceof JobInterface)) {
            throw new InvalidArgumentException('Job must be instance of JobInterface.');
        }

        if ($event->ttr <= 0) {
            throw new InvalidArgumentException('Job TTR must be greater that zero.');
        }

        if ($event->delay < 0) {
            throw new InvalidArgumentException('Job delay must be positive.');
        }

        $message = $this->serializer->serialize($event->job);
        $event->id = $this->pushMessage($message, $event->ttr, $event->delay, $event->priority);
        $this->trigger(self::EVENT_AFTER_PUSH, $event);

        return $event->id;
    }

    /**
     * @param string $payload
     * @param int $ttr time to reserve in seconds
     * @param int $delay
     * @param mixed $priority
     * @return string|int|null id of a job message
     */
    abstract protected function pushMessage(string $payload, int $ttr, int $delay, mixed $priority): int|string|null;

    /**
     * Uses for CLI drivers and gets process ID of a worker.
     *
     * @since 2.0.2
     */
    public function getWorkerPid(): ?int
    {
        return null;
    }

    /**
     * @param int|string $id of a job message
     * @param string $message
     * @param int $ttr time to reserve
     * @param int $attempt number
     * @return bool
     */
    protected function handleMessage(int|string $id, string $message, int $ttr, int $attempt): bool
    {
        [$job, $error] = $this->unserializeMessage($message);
        $event = new ExecEvent([
            'id' => $id,
            'job' => $job,
            'ttr' => $ttr,
            'attempt' => $attempt,
            'error' => $error,
        ]);
        $this->trigger(self::EVENT_BEFORE_EXEC, $event);
        if ($event->handled) {
            return true;
        }
        if ($event->error) {
            return $this->handleError($event);
        }
        try {
            $event->result = $event->job->execute($this);
        } catch (\Exception|\Throwable $error) {
            $event->error = $error;
            return $this->handleError($event);
        }
        $this->trigger(self::EVENT_AFTER_EXEC, $event);
        return true;
    }

    /**
     * Unserializes.
     *
     * @param string $serialized message
     * @return array pair of a job and error that
     */
    public function unserializeMessage(string $serialized): array
    {
        try {
            $job = $this->serializer->unserialize($serialized);
        } catch (\Exception $e) {
            return [null, new InvalidJobException($serialized, $e->getMessage(), 0, $e)];
        }

        if ($job instanceof JobInterface) {
            return [$job, null];
        }

        return [null, new InvalidJobException($serialized, sprintf(
            'Job must be a JobInterface instance instead of %s.',
            VarDumper::dumpAsString($job)
        ))];
    }

    /**
     * @param ExecEvent $event
     * @return bool
     * @internal
     */
    public function handleError(ExecEvent $event): bool
    {
        $event->retry = $event->attempt < $this->attempts;
        if ($event->error instanceof InvalidJobException) {
            $event->retry = false;
        } elseif ($event->job instanceof RetryableJobInterface) {
            $event->retry = $event->job->canRetry($event->attempt, $event->error);
        }
        $this->trigger(self::EVENT_AFTER_ERROR, $event);
        return !$event->retry;
    }

    /**
     * @param int|string $id of a job message
     * @return bool
     */
    public function isWaiting(int|string $id): bool
    {
        return $this->status($id) === self::STATUS_WAITING;
    }

    /**
     * @param int|string $id of a job message
     * @return bool
     */
    public function isReserved(int|string $id): bool
    {
        return $this->status($id) === self::STATUS_RESERVED;
    }

    /**
     * @param int|string $id of a job message
     * @return bool
     */
    public function isDone(int|string $id): bool
    {
        return $this->status($id) === self::STATUS_DONE;
    }

    /**
     * @param string|int $id of a job message
     * @return int status code
     */
    abstract public function status(int|string $id): int;
}
