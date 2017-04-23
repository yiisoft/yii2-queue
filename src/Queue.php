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

    const STATUS_UNKNOWN = 0;
    const STATUS_WAITING = 1;
    const STATUS_STARTED = 2;
    const STATUS_FINISHED = 3;

    /**
     * @var Serializer|array
     */
    public $serializer = PhpSerializer::class;

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
        $event = new PushEvent(['job' => $job, 'timeout' => 0]);
        $this->trigger(self::EVENT_BEFORE_PUSH, $event);
        $event->id = $this->pushMessage($this->serializer->serialize($event->job), $event->timeout);
        $this->trigger(self::EVENT_AFTER_PUSH, $event);

        return $event->id;
    }

    /**
     * @param Job|mixed $job
     * @param integer $timeout
     * @return string|null id of a job message
     */
    public function later($job, $timeout)
    {
        $event = new PushEvent(['job' => $job, 'timeout' => $timeout]);
        $this->trigger(self::EVENT_BEFORE_PUSH, $event);
        $event->id = $this->pushMessage($this->serializer->serialize($event->job), $event->timeout);
        $this->trigger(self::EVENT_AFTER_PUSH, $event);

        return $event->id;
    }

    /**
     * @param string $message
     * @param int $timeout
     * @return string|null id of a job message
     */
    abstract protected function pushMessage($message, $timeout);

    /**
     * @param string $id of a job message
     * @return int status code
     */
    abstract public function status($id);

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
            $job->execute($this, $id);
        } catch (\Exception $error) {
            $this->trigger(self::EVENT_AFTER_EXEC_ERROR, new ErrorEvent(['id' => $id, 'job' => $job, 'error' => $error]));
        }
        if (!$error) {
            $this->trigger(self::EVENT_AFTER_EXEC, new JobEvent(['id' => $id, 'job' => $job]));
        }

        return !$error;
    }
}