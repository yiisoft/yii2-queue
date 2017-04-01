<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\db;

use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\console\Controller as ConsoleController;
use yii\db\Query;
use yii\helpers\Console;


/**
 * Info about queue status.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class InfoAction extends Action
{
    /**
     * @var Queue
     */
    public $queue;

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
            throw new InvalidConfigException('The queue must be db queue.');
        }
    }

    /**
     * Info about queue status.
     */
    public function run()
    {
        Console::output(Console::ansiFormat('Jobs', [Console::FG_GREEN]));

        Console::stdout(Console::ansiFormat('- reserved: ', [Console::FG_YELLOW]));
        Console::output($this->getReserved()->count('*', $this->queue->db));

        Console::stdout(Console::ansiFormat('- delayed: ', [Console::FG_YELLOW]));
        Console::output($this->getDelayed()->count('*', $this->queue->db));

        Console::stdout(Console::ansiFormat('- started: ', [Console::FG_YELLOW]));
        Console::output($this->getStarted()->count('*', $this->queue->db));

        Console::stdout(Console::ansiFormat('- finished: ', [Console::FG_YELLOW]));
        Console::output($this->getFinished()->count('*', $this->queue->db));
    }

    /**
     * @return Query
     */
    protected function getReserved()
    {
        return (new Query())
            ->from($this->queue->tableName)
            ->andWhere(['channel' => $this->queue->channel])
            ->andWhere(['started_at' => null])
            ->andWhere(['timeout' => 0]);
    }

    /**
     * @return Query
     */
    protected function getDelayed()
    {
        return (new Query())
            ->from($this->queue->tableName)
            ->andWhere(['channel' => $this->queue->channel])
            ->andWhere(['started_at' => null])
            ->andWhere(['>', 'timeout', 0]);
    }

    /**
     * @return Query
     */
    protected function getStarted()
    {
        return (new Query())
            ->from($this->queue->tableName)
            ->andWhere(['channel' => $this->queue->channel])
            ->andWhere('[[started_at]] is not null')
            ->andWhere(['finished_at' => null]);
    }

    /**
     * @return Query
     */
    protected function getFinished()
    {
        return (new Query())
            ->from($this->queue->tableName)
            ->andWhere(['channel' => $this->queue->channel])
            ->andWhere('[[finished_at]] is not null');
    }
}