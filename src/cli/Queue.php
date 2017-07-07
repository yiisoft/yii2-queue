<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\cli;

use Yii;
use yii\base\BootstrapInterface;
use yii\base\InvalidConfigException;
use yii\console\Application as ConsoleApp;
use yii\helpers\Inflector;
use zhuravljov\yii\queue\Queue as BaseQueue;

/**
 * Queue with CLI
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class Queue extends BaseQueue implements BootstrapInterface
{
    /**
     * @var string command class name
     */
    public $commandClass = __NAMESPACE__ . "\\" . get_class(new Command());
    /**
     * @var array of additional options of command
     */
    public $commandOptions = [];
    /**
     * @var callable|null
     * @internal only for command
     */
    public $messageHandler;

    /**
     * @return string command id
     * @throws
     */
    protected function getCommandId()
    {
        foreach (Yii::$app->getComponents(false) as $id => $component) {
            if ($component === $this) {
                return Inflector::camel2id($id);
            }
        }
        throw new InvalidConfigException('Queue must be an application component.');
    }

    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        if ($app instanceof ConsoleApp) {
            $app->controllerMap[$this->getCommandId()] = [
                'class' => $this->commandClass,
                'queue' => $this,
            ] + $this->commandOptions;
        }
    }

    /**
     * @inheritdoc
     */
    protected function handleMessage($id, $message)
    {
        if ($this->messageHandler) {
            return call_user_func($this->messageHandler, $id, $message);
        } else {
            return parent::handleMessage($id, $message);
        }
    }

    /**
     * @param string|null $id of a message
     * @param string $message
     * @return bool
     * @internal only for command
     */
    public function execute($id, $message)
    {
        return parent::handleMessage($id, $message);
    }
}