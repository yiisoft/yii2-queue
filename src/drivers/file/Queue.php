<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\file;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\base\NotSupportedException;
use yii\helpers\FileHelper;
use zhuravljov\yii\queue\cli\Queue as CliQueue;
use zhuravljov\yii\queue\cli\Signal;

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
            } elseif (!empty($data['waiting'])) {
                $id = array_shift($data['waiting']);
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
    protected function pushMessage($message, $delay, $priority)
    {
        if ($priority !== null) {
            throw new NotSupportedException('Job priority is not supported in the driver.');
        }

        $this->touchIndex("$this->path/index.data", function ($data) use ($message, $delay, &$id) {
            if (!isset($data['lastId'])) {
                $data['lastId'] = 0;
            }
            $id = ++$data['lastId'];
            file_put_contents("$this->path/job$id.data", $message);
            if (!$delay) {
                $data['waiting'][] = $id;
            } else {
                $data['delayed'][] = [$id, time() + $delay];
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
    protected function status($id)
    {
        if (!is_numeric($id) || $id <= 0) {
            throw new InvalidParamException("Unknown messages ID: $id.");
        }

        if (file_exists("$this->path/job$id.data")) {
            return self::STATUS_WAITING;
        } else {
            return self::STATUS_DONE;
        }
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