<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\cli;

use yii\base\Action as BaseAction;
use yii\base\InvalidConfigException;
use yii\console\Controller as ConsoleController;

/**
 * Class Action
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class Action extends BaseAction
{
    /**
     * @var Queue
     */
    public $queue;
    /**
     * @var Command|ConsoleController
     */
    public $controller;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (!$this->queue && ($this->controller instanceof Command)) {
            $this->queue = $this->controller->queue;
        }
        if (!($this->controller instanceof ConsoleController)) {
            throw new InvalidConfigException('The controller must be console controller.');
        }
        if (!($this->queue instanceof Queue)) {
            throw new InvalidConfigException('The queue must be cli queue.');
        }
    }

    /**
     * @param string $string
     * @return string
     */
    protected function format($string)
    {
        return call_user_func_array([$this->controller, 'ansiFormat'], func_get_args());
    }
}