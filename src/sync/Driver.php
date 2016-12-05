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
    /**
     * @var boolean
     */
    public $handle = false;

    private $_messages = [];

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
    public function init()
    {
        parent::init();
        if ($this->handle) {
            Yii::$app->on(Application::EVENT_AFTER_REQUEST, function () {
                ob_start();
                $this->run();
                ob_clean();
            });
        }
    }

    /**
     * Run jobs from all channels.
     */
    public function run()
    {
        while ($this->_messages) {
            $messages = reset($this->_messages);
            $channel = key($this->_messages);
            array_shift($this->_messages);
            foreach ($messages as $message) {
                $job = unserialize($message);
                $this->getQueue()->run($channel, $job);
            }
        }
    }
}