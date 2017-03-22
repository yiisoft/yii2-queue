<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue;

use yii\base\Object;
use yii\di\Instance;
use zhuravljov\yii\queue\serializers\PhpSerializer;
use zhuravljov\yii\queue\serializers\Serializer;

/**
 * Queue driver interface
 *
 * @property Queue $queue
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class Driver extends Object
{
    /**
     * @var Serializer|array
     */
    public $serializer = PhpSerializer::class;

    /**
     * @var Queue
     */
    private $_queue;

    /**
     * @param Queue $queue
     * @inheritdoc
     */
    public function __construct(Queue $queue, $config = [])
    {
        $this->_queue = $queue;
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->serializer = Instance::ensure($this->serializer, Serializer::class);
    }

    /**
     * @return Queue
     */
    protected function getQueue()
    {
        return $this->_queue;
    }

    /**
     * @param Job|mixed $job
     * @return string
     */
    protected function serialize($job)
    {
        return $this->serializer->serialize($job);
    }

    /**
     * @param string $serialized
     * @return Job
     */
    protected function unserialize($serialized)
    {
        return $this->serializer->unserialize($serialized);
    }

    /**
     * Pushes job to the storage.
     *
     * @param Job|mixed $job
     */
    abstract public function push($job);

    /**
     * Pushes job to the storage.
     *
     * @param Job|mixed $job
     * @param integer $timeout
     */
    abstract public function later($job, $timeout);
}