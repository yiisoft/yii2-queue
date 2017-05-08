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
    /**
     * @var int default time to run a job
     */
    public $ttr = 60;
    /**
     * @var int default attempt count
     */
    public $attempts = 1;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->serializer = Instance::ensure($this->serializer, Serializer::class);
    }

    /**
     * @param Job|mixed $job
     * @return string|null id of a job message
     */
    public function push($job)
    {
        $ttr = $job instanceof RetryableJob ? $job->getTtr() : $this->ttr;
        $event = new PushEvent(['job' => $job, 'ttr' => $ttr, 'delay' => 0]);
        $this->trigger(self::EVENT_BEFORE_PUSH, $event);
        $message = $this->serializer->serialize($event->job);
        $event->id = $this->pushMessage($message, $event->ttr, $event->delay);
        $this->trigger(self::EVENT_AFTER_PUSH, $event);

        return $event->id;
    }

    /**
     * @param Job|mixed $job
     * @param integer $delay
     * @return string|null id of a job message
     */
    public function later($job, $delay)
    {
        $ttr = $job instanceof RetryableJob ? $job->getTtr() : $this->ttr;
        $event = new PushEvent(['job' => $job, 'ttr' => $ttr, 'delay' => $delay]);
        $this->trigger(self::EVENT_BEFORE_PUSH, $event);
        $message = $this->serializer->serialize($event->job);
        $event->id = $this->pushMessage($message, $event->ttr, $event->delay);
        $this->trigger(self::EVENT_AFTER_PUSH, $event);

        return $event->id;
    }

    /**
     * @param string $message
     * @param int $ttr time to run in seconds
     * @param int $delay
     * @return string|null id of a job message
     */
    abstract protected function pushMessage($message, $ttr, $delay);

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