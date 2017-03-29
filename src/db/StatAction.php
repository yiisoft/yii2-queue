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
 * Statistic Action
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class StatAction extends Action
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
        if (!$this->controller instanceof ConsoleController) {
            throw new InvalidConfigException('The controller must be console controller.');
        }
        if (!($this->queue instanceof Queue)) {
            throw new InvalidConfigException('The queue must be db queue.');
        }
    }

    /**
     * Returns db-queue statistic.
     */
    public function run()
    {
        echo Console::ansiFormat('Jobs', [Console::FG_GREEN]);
        echo PHP_EOL;
        echo Console::ansiFormat('- reserved: ', [Console::FG_YELLOW]);
        echo $this->getReservedCount();
        echo PHP_EOL;
        echo Console::ansiFormat('- started: ', [Console::FG_YELLOW]);
        echo $this->getStartedCount();
        echo PHP_EOL;
        echo Console::ansiFormat('- finished: ', [Console::FG_YELLOW]);
        echo $this->getFinishedCount();
        echo PHP_EOL;
    }

    /**
     * @return int
     */
    protected function getReservedCount()
    {
        return (new Query())
            ->from($this->queue->tableName)
            ->andWhere(['channel' => $this->queue->channel])
            ->andWhere(['started_at' => null])
            ->count('*', $this->queue->db);
    }

    /**
     * @return int
     */
    protected function getStartedCount()
    {
        return (new Query())
            ->from($this->queue->tableName)
            ->andWhere(['channel' => $this->queue->channel])
            ->andWhere('[[started_at]] is not null')
            ->andWhere(['finished_at' => null])
            ->count('*', $this->queue->db);
    }

    /**
     * @return int
     */
    protected function getFinishedCount()
    {
        return (new Query())
            ->from($this->queue->tableName)
            ->andWhere(['channel' => $this->queue->channel])
            ->andWhere('[[finished_at]] is not null')
            ->count('*', $this->queue->db);
    }
}