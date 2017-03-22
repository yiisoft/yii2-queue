<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\sync;

use Yii;
use yii\base\Application;
use yii\base\NotSupportedException;
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
                $this->run();
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

    /**
     * @inheritdoc
     */
    public function later($job, $timeout)
    {
        throw new NotSupportedException('Delayed work is not supported in the driver.');
    }

    /**
     * Runs all jobs from queue.
     */
    public function run()
    {
        while (($message = array_shift($this->_messages)) !== null) {
            $job = $this->unserialize($message);
            $this->getQueue()->run($job);
        }
    }
}