<?php
/**
 * User: Pavle Lee <523260513@qq.com>
 * Date: 2017/5/15
 * Time: 15:59
 */

namespace zhuravljov\yii\queue\drivers\dispatcher;

use zhuravljov\yii\queue\cli\Queue as CliQueue;

class Queue extends CliQueue
{
    /**
     * @var CliQueue[]
     */
    public $group;

    /**
     * @var string command class name
     */
    public $commandClass = Command::class;

    public function init()
    {
        parent::init();

        foreach ($this->group as $key => $queue) {
            $this->group[$key] = \Yii::createObject($queue);
        }
    }


    /**
     * Runs all jobs from db-queue.
     */
    public function run()
    {
        foreach ($this->group as $queue) {
            if (method_exists($queue, 'run')) {
                $queue->run();
            }
        }
    }

    /**
     * Listens file-queue and runs new jobs.
     */
    public function listen()
    {
        $cid = [];
        foreach ($this->group as $queue) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                die('fork failed');
            } elseif ($pid == 0) {
                $cid[] = getmypid();
                if (method_exists($queue, 'listen')) {
                    $args = func_get_args();
                    call_user_func_array([$queue, 'listen'], $args);
                }
            }
        }

        pcntl_wait($status);
    }

    /**
     * @return int
     */
    protected function getDispatcher()
    {
        return mt_rand(0, count($this->group) - 1);
    }

    /**
     * @param string $message
     * @param int $timeout
     * @return string|null id of a job message
     */
    protected function pushMessage($message, $timeout)
    {
        $idx = $this->getDispatcher();
        $queue = $this->group[$idx];

        return "{$idx}:" . $queue->pushMessage($message, $timeout);
    }

    /**
     * @param string $id of a job message
     * @return int status code
     */
    protected function status($id)
    {
        list($idx, $id) = explode(':', $id);
        $queue = $this->group[$idx];

        return $queue->status($id);
    }
}