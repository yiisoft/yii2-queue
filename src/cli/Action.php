<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue\cli;

use yii\base\Action as BaseAction;
use yii\base\InvalidConfigException;
use yii\console\Controller as ConsoleController;

/**
 * Base Command Action.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class Action extends BaseAction
{
    /**
     * @var Queue
     */
    public Queue $queue;
    /**
     * @var Command|ConsoleController
     */
    public $controller;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if ($this->controller instanceof Command) {
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
    protected function format(string $string): string
    {
        return call_user_func_array([$this->controller, 'ansiFormat'], func_get_args());
    }
}
