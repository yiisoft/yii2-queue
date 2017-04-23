<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\drivers\file;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\helpers\FileHelper;
use zhuravljov\yii\queue\CliQueue;
use zhuravljov\yii\queue\Signal;

/**
 * File Queue
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Queue extends CliQueue
{
    public $path = '@runtime/queue';
    public $dirMode = 0755;

    public $commandClass = Command::class;

    public function init()
    {
        parent::init();
        $this->path = Yii::getAlias($this->path);
        if (!is_dir($this->path)) {
            FileHelper::createDirectory($this->path, $this->dirMode, true);
        }
    }

    /**
     * Runs all jobs from db-queue.
     */
    public function run()
    {
        while (!Signal::isExit() && ($payload = $this->pop()) !== null) {
            list($id, $message) = $payload;
            $this->handleMessage($id, $message);
        }
    }

    /**
     * Listens file-queue and runs new jobs.
     *
     * @param integer $delay number of seconds for waiting new job.
     */
    public function listen($delay)
    {
        do {
            $this->run();
        } while (!$delay || sleep($delay) === 0);
    }

    /**
     * @return string|null message
     */
    protected function pop()
    {
        $id = null;
        $message = null;
        $this->touchIndex("$this->path/index.data", function ($data) use (&$message, &$id) {
            if (!empty($data['delayed']) && $data['delayed'][0][1] <= time()) {
                list($id, $time) = array_shift($data['delayed']);
            } elseif (!empty($data['reserved'])) {
                $id = array_shift($data['reserved']);
            }
            if ($id) {
                $message = file_get_contents("$this->path/job$id.data");
                unlink("$this->path/job$id.data");
            }
            return $data;
        });

        if ($id) {
            return [$id, $message];
        } else {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    protected function pushMessage($message, $timeout)
    {
        $this->touchIndex("$this->path/index.data", function ($data) use ($message, $timeout, &$id) {
            if (!isset($data['lastId'])) {
                $data['lastId'] = 0;
            }
            $id = ++$data['lastId'];
            file_put_contents("$this->path/job$id.data", $message);
            if (!$timeout) {
                $data['reserved'][] = $id;
            } else {
                $data['delayed'][] = [$id, time() + $timeout];
                usort($data['delayed'], function ($a, $b) {
                    if ($a[1] < $b[1]) return -1;
                    if ($a[1] > $b[1]) return 1;
                    if ($a[0] < $b[0]) return -1;
                    if ($a[0] > $b[0]) return 1;
                    return 0;
                });
            }
            return $data;
        });

        return $id;
    }

    /**
     * @inheritdoc
     */
    public function status($id)
    {
        throw new NotSupportedException('Status is not supported in the driver.');
    }

    /**
     * @param string $fileName
     * @param callable $callback
     * @throws InvalidConfigException
     */
    private function touchIndex($fileName, $callback)
    {
        touch($fileName);
        if (($file = fopen($fileName, 'r+')) === false) {
            throw new InvalidConfigException("Unable to open index file: $fileName");
        }
        flock($file, LOCK_EX);
        $content = stream_get_contents($file);
        $data = $content === '' ? [] : unserialize($content);
        try {
            $result = call_user_func($callback, $data);
            $newContent = serialize($result);
            if ($newContent !== $content) {
                ftruncate($file, 0);
                rewind($file);
                fwrite($file, $newContent);
                fflush($file);
            }
        } finally {
            flock($file, LOCK_UN);
            fclose($file);
        }
    }
}