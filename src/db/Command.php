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
     * @var \zhuravljov\yii\queue\Queue
     */
    public $queue;

    /**
     * List of channels.
     */
    public function actionChannels()
    {
        /** @var Driver $driver */
        $driver = $this->queue->driver;
        $rows = (new Query())
            ->select(['channel', 'count' => 'SUM(started_at IS NULL)'])
            ->from($driver->tableName)
            ->groupBy(['channel'])
            ->orderBy(['channel' => SORT_ASC])
            ->all($driver->db);

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
        $this->stdout("Worker has started.\n", Console::FG_GREEN);
        $this->queue->attachBehavior('verbose', VerboseBehavior::class);
        $count = $this->queue->work($channel);
        $this->stdout("$count jobs have been run.\n", Console::FG_GREEN);
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
        $this->stdout(date('Y-m-d H:i:s') . ": worker has started.\n", Console::FG_GREEN);
        $this->queue->attachBehavior('verbose', VerboseBehavior::class);
        do {
            $this->queue->work($channel);
        } while (!$delay || sleep($delay) === 0);
    }

    /**
     * Purges the db-queue.
     *
     * @param string $channel
     */
    public function actionPurge($channel)
    {
        $this->queue->purge($channel);
    }
}