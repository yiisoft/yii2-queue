<?php

namespace zhuravljov\yii\queue\sync;

use Yii;
use yii\base\Application;
use zhuravljov\yii\queue\Driver as BaseDriver;

/**
 * Sync Driver
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Driver extends BaseDriver
{
    /**
     * @var boolean
     */
    public $handle = false;
    /**
     * @var array
     */
    private $_messages = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if ($this->handle) {
            Yii::$app->on(Application::EVENT_AFTER_REQUEST, function () {
                ob_start();
                while (($message = array_shift($this->_messages)) !== null) {
                    $job = $this->unserialize($message);
                    $this->getQueue()->run($job);
                }
                ob_clean();
            });
        }
    }

    /**
     * @inheritdoc
     */
    public function push($job)
    {
        $this->_messages[] = $this->serialize($job);
    }
}