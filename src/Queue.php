<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue;

use Yii;
use yii\base\Component;
use yii\base\InvalidParamException;
use yii\di\Instance;
use zhuravljov\yii\queue\serializers\Serializer;
use zhuravljov\yii\queue\serializers\PhpSerializer;

/**
 * Base Queue
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class Queue extends Component
{
    /**
     * @event PushEvent
     */
    const EVENT_BEFORE_PUSH = 'beforePush';
    /**
     * @event PushEvent
     */
    const EVENT_AFTER_PUSH = 'afterPush';
    /**
     * @event JobEvent
     */
    const EVENT_BEFORE_EXEC = 'beforeExec';
    /**
     * @event JobEvent
     */
    const EVENT_AFTER_EXEC = 'afterExec';
    /**
     * @event ErrorEvent
     */
    const EVENT_AFTER_EXEC_ERROR = 'afterExecError';
    /**
     * @see Queue::isWaiting()
     */
    const STATUS_WAITING = 1;
    /**
     * @see Queue::isReserved()
     */
    const STATUS_RESERVED = 2;
    /**
     * @see Queue::isDone()
     */
    const STATUS_DONE = 3;

    /**
     * @var Serializer|array
     */
    public $serializer = PhpSerializer::class;

    private $pushDelay = 0;
    private $pushPriority;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->serializer = Instance::ensure($this->serializer, Serializer::class);
    }

    /**
     * Sets delay for later execute
     *
     * @param int|mixed $value
     * @return $this
     */
    public function delay($value)
    {
        $this->pushDelay = $value;
        return $this;
    }

    /**
     * Sets job priority
     *
     * @param mixed $value
     * @return $this
     */
    public function priority($value)
    {
        $this->pushPriority = $value;
        return $this;
    }

    /**
     * Pushes job into queue
     *
     * @param Job|mixed $job
     * @return string|null id of a job message
     */
    public function push($job)
    {
        $event = new PushEvent([
            'job' => $job,
            'delay' => $this->pushDelay,
            'priority' => $this->pushPriority,
        ]);
        $this->pushDelay = 0;
        $this->pushPriority = null;

        $this->trigger(self::EVENT_BEFORE_PUSH, $event);
        $event->id = $this->pushMessage(
            $this->serializer->serialize($event->job),
            $event->delay,
            $event->priority
        );
        $this->trigger(self::EVENT_AFTER_PUSH, $event);

        return $event->id;
    }

    /**
     * @param Job|mixed $job
     * @param int|mixed $delay
     * @return string|null id of a job message
     * @deprecated will be removed in 1.1
     */
    public function later($job, $delay)
    {
        return $this->delay($delay)->push($job);
    }

    /**
     * @param string $message
     * @param int $delay
     * @param mixed $priority
     * @return string|null id of a job message
     */
    abstract protected function pushMessage($message, $delay, $priority);

    /**
     * @param string|null $id of a job message
     * @param string $message
     * @return boolean
     */
    protected function handleMessage($id, $message)
    {
        $job = $this->serializer->unserialize($message);
        if (!($job instanceof Job)) {
            throw new InvalidParamException('Message must be ' . Job::class . ' object.');
        }

        $error = null;
        $this->trigger(self::EVENT_BEFORE_EXEC, new JobEvent(['id' => $id, 'job' => $job]));
        try {
            $job->execute($this);
        } catch (\Exception $error) {
            $this->trigger(self::EVENT_AFTER_EXEC_ERROR, new ErrorEvent(['id' => $id, 'job' => $job, 'error' => $error]));
        }
        if (!$error) {
            $this->trigger(self::EVENT_AFTER_EXEC, new JobEvent(['id' => $id, 'job' => $job]));
        }

        return !$error;
    }

    /**
     * @param string $id of a job message
     * @return bool
     */
    public function isWaiting($id)
    {
        return $this->status($id) === Queue::STATUS_WAITING;
    }

    /**
     * @param string $id of a job message
     * @return bool
     */
    public function isReserved($id)
    {
        return $this->status($id) === Queue::STATUS_RESERVED;
    }

    /**
     * @param string $id of a job message
     * @return bool
     */
    public function isDone($id)
    {
        return $this->status($id) === Queue::STATUS_DONE;
    }

    /**
     * @param string $id of a job message
     * @return int status code
     */
    abstract protected function status($id);
}