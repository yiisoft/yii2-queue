<?php

namespace zhuravljov\yii\queue;

use yii\base\Object;

/**
 * Class Driver
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
     * @param Job $job
     * @return mixed $message
     */
    abstract public function push($job);

    /**
     * @param mixed $message
     * @param Job $job
     * @return boolean
     */
    abstract public function pop(&$message, &$job);

    /**
     * @param $message
     */
    abstract public function release($message);
}