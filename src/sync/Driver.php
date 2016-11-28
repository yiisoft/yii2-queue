<?php

namespace zhuravljov\yii\queue\sync;

use Yii;
use yii\base\Application;
use zhuravljov\yii\queue\Driver as BaseDriver;

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
            foreach (array_keys($this->_messages) as $channel) {
                $this->getQueue()->run($channel);
            }
            Yii::trace(ob_get_clean(), __CLASS__);
            Yii::info("Jobs have been complete.", __CLASS__);
        });
    }

    /**
     * @inheritdoc
     */
    public function push($channel, $job)
    {
        $this->_messages[$channel][] = serialize($job);
    }

    /**
     * @inheritdoc
     */
    public function run($channel, $handler)
    {
        $count = 0;
        if (!empty($this->_messages[$channel])) {
            while (($message = array_shift($this->_messages[$channel])) !== null) {
                $count++;
                $job = unserialize($message);
                call_user_func($handler, $job);
            }
        }
        return $count;
    }

    /**
     * @inheritdoc
     */
    public function purge($channel)
    {
        $this->_messages[$channel] = [];
    }
}