<?php

namespace zhuravljov\yii\queue\drivers;

use zhuravljov\yii\queue\Driver;

/**
 * Class SyncDriver
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class SyncDriver extends Driver
{
    private $_messages = [];

    /**
     * @inheritdoc
     */
    public function push($job)
    {
        $this->_messages[] = serialize($job);
        $this->getQueue()->work(false);
    }

    /**
     * @inheritdoc
     */
    public function pop(&$message, &$job)
    {
        $message = array_pop($this->_messages);
        if ($message !== null) {
            $job = unserialize($message);
            return true;
        } else {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function release($message)
    {
    }

    /**
     * @inheritdoc
     */
    public function purge()
    {
        $this->_messages = [];
    }
}