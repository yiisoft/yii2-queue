<?php

namespace zhuravljov\yii\queue\sync;

use Yii;
use yii\base\Application;
use zhuravljov\yii\queue\BaseDriver;

/**
 * Class SyncDriver
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Driver extends BaseDriver
{
    private $_messages = [];

    public function init()
    {
        parent::init();
        Yii::$app->on(Application::EVENT_AFTER_REQUEST, function () {
            Yii::info('Worker has been started.', __CLASS__);
            ob_start();
            $count = 0;
            while ($this->getQueue()->work(false)) {
                $count++;
            }
            Yii::trace(ob_get_clean(), __CLASS__);
            Yii::info("$count jobs have been complete.", __CLASS__);
        });
    }

    /**
     * @inheritdoc
     */
    public function push($job)
    {
        $this->_messages[] = serialize($job);
    }

    /**
     * @inheritdoc
     */
    public function pop(&$message, &$job)
    {
        $message = array_shift($this->_messages);
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