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
use zhuravljov\yii\queue\Queue as BaseQueue;

/**
 * Sync Queue
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Queue extends BaseQueue
{
    /**
     * @var boolean
     */
    public $handle = false;
    /**
     * @var array
     */
    private $messages = [];

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
     * Runs all jobs from queue.
     */
    public function run()
    {
        while (($message = array_shift($this->messages)) !== null) {
            $this->handleMessage($message);
        }
    }

    /**
     * @inheritdoc
     */
    protected function sendMessage($message, $timeout)
    {
        if ($timeout) {
            throw new NotSupportedException('Delayed work is not supported in the driver.');
        }

        $this->messages[] = $message;
    }
}