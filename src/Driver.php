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
     * Pushes job to the storage.
     *
     * @param Job $job
     * @return mixed $message
     */
    abstract public function push($job);

    /**
     * @param callable $handler
     * @return integer count of jobs that has been handled
     */
    abstract public function work($handler);

    /**
     * Purges the storage.
     */
    abstract public function purge();
}