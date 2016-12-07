<?php

namespace zhuravljov\yii\queue;

use yii\base\Object;

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
     * @var callable
     */
    public $serializer = 'serialize';

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
        return call_user_func($this->serializer, $job);
    }

    /**
     * @param string $serialized
     * @return Job
     */
    protected function unserialize($serialized)
    {
        return unserialize($serialized);
    }

    /**
     * Pushes job to the storage.
     *
     * @param Job|mixed $job
     */
    abstract public function push($job);
}