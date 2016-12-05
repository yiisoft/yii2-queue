<?php

namespace zhuravljov\yii\queue\db;

use yii\console\Controller;
use yii\db\Query;
use yii\helpers\Console;
use zhuravljov\yii\queue\VerboseBehavior;

/**
 * Manages application db-queue.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Command extends Controller
{
    /**
     * @var Driver
     */
    public $driver;

    /**
     * List of channels.
     */
    public function actionChannels()
    {
        $rows = (new Query())
            ->select(['channel', 'count' => 'SUM(started_at IS NULL)'])
            ->from($this->driver->tableName)
            ->groupBy(['channel'])
            ->orderBy(['channel' => SORT_ASC])
            ->all($this->driver->db);

        foreach ($rows as $row) {
            $this->stdout('- ');
            $this->stdout($row['channel'], Console::FG_YELLOW);
            $this->stdout(": $row[count] jobs\n");
        }
    }

    /**
     * Runs all jobs from db-queue.
     * It can be used as cron job.
     *
     * @param string $channel
     */
    public function actionRun($channel)
    {
        $this->driver->queue->attachBehavior('verbose', VerboseBehavior::class);
        $this->driver->run($channel);
    }

    /**
     * Listens db-queue and runs new jobs.
     * It can be used as demon process.
     *
     * @param string $channel
     * @param integer $delay Number of seconds for waiting new job.
     */
    public function actionListen($channel, $delay = 3)
    {
        $this->driver->queue->attachBehavior('verbose', VerboseBehavior::class);
        do {
            $this->driver->run($channel);
        } while (!$delay || sleep($delay) === 0);
    }
}