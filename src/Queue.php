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
     */
    public function push($job)
    {
        $event = new PushEvent(['job' => $job, 'timeout' => 0]);
        $this->trigger(self::EVENT_BEFORE_PUSH, $event);
        $this->sendMessage($this->serializer->serialize($event->job), $event->timeout);
        $this->trigger(self::EVENT_AFTER_PUSH, $event);
    }

    /**
     * @param Job|mixed $job
     * @param integer $timeout
     */
    public function later($job, $timeout)
    {
        $event = new PushEvent(['job' => $job, 'timeout' => $timeout]);
        $this->trigger(self::EVENT_BEFORE_PUSH, $event);
        $this->sendMessage($this->serializer->serialize($event->job), $event->timeout);
        $this->trigger(self::EVENT_AFTER_PUSH, $event);
    }

    /**
     * @param string $message
     * @param int $timeout
     */
    abstract protected function sendMessage($message, $timeout);

    /**
     * @param string $message
     * @return boolean
     */
    protected function handleMessage($message)
    {
        $job = $this->serializer->unserialize($message);
        if (!($job instanceof Job)) {
            throw new InvalidParamException('Message must be ' . Job::class . ' object.');
        }

        $error = null;
        $this->trigger(self::EVENT_BEFORE_EXEC, new JobEvent(['job' => $job]));
        try {
            $job->execute();
        } catch (\Exception $error) {
            $this->trigger(self::EVENT_AFTER_EXEC_ERROR, new ErrorEvent(['job' => $job, 'error' => $error]));
        }
        if (!$error) {
            $this->trigger(self::EVENT_AFTER_EXEC, new JobEvent(['job' => $job]));
        }

        return !$error;
    }
}