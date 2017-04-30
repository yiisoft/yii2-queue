<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\db;

use yii\db\Query;
use yii\helpers\Console;
use zhuravljov\yii\queue\cli\Action;


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