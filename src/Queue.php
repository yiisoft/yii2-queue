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
use yii\helpers\Inflector;
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
     * @event JobEvent
     */
    const EVENT_AFTER_PUSH = 'afterPush';
    /**
     * @event JobEvent
     */
    const EVENT_BEFORE_WORK = 'beforeWork';
    /**
     * @event JobEvent
     */
    const EVENT_AFTER_WORK = 'afterWork';
    /**
     * @event ErrorEvent
     */
    const EVENT_AFTER_ERROR = 'afterError';

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
        $this->sendMessage($this->serializer->serialize($job), 0);
        $this->trigger(self::EVENT_AFTER_PUSH, new JobEvent(['job' => $job]));
    }

    /**
     * @param Job|mixed $job
     * @param integer $timeout
     */
    public function later($job, $timeout)
    {
        $this->sendMessage($this->serializer->serialize($job), $timeout);
        $this->trigger(self::EVENT_AFTER_PUSH, new JobEvent(['job' => $job]));
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
        $this->trigger(self::EVENT_BEFORE_WORK, new JobEvent(['job' => $job]));
        try {
            $job->run();
        } catch (\Exception $error) {
            $this->trigger(self::EVENT_AFTER_ERROR, new ErrorEvent(['job' => $job, 'error' => $error]));
        }
        if (!$error) {
            $this->trigger(self::EVENT_AFTER_WORK, new JobEvent(['job' => $job]));
        }

        return !$error;
    }

    /**
     * @return string component id
     */
    protected function getId()
    {
        foreach (Yii::$app->getComponents(false) as $id => $component) {
            if ($component === $this) {
                return Inflector::camel2id($id);
            }
        }
        return null;
    }
}