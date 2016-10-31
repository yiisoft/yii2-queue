<?php

namespace zhuravljov\yii\queue\sync;

use zhuravljov\yii\queue\BaseDriver;

/**
 * Class SyncDriver
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Driver extends BaseDriver
{
    private $_messages = [];
    private $_locked = false;

    /**
     * @inheritdoc
     */
    public function push($job)
    {
        $this->_messages[] = serialize($job);
        while ($this->getQueue()->work(false));
    }

    /**
     * @inheritdoc
     */
    public function pop(&$message, &$job)
    {
        if (!$this->_locked) {
            $message = array_shift($this->_messages);
            if ($message !== null) {
                $this->_locked = true;
                $job = unserialize($message);
                return true;
            }
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function release($message)
    {
        $this->_locked = false;
    }

    /**
     * @inheritdoc
     */
    public function purge()
    {
        $this->_messages = [];
    }
}