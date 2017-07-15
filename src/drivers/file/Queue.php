<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\file;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\base\NotSupportedException;
use yii\helpers\FileHelper;
use yii\queue\cli\Queue as CliQueue;
use yii\queue\cli\Signal;

/**
 * File Queue
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Queue extends CliQueue
{
    public $path = '@runtime/queue';
    public $dirMode = 0755;
    public $fileMode;

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
        while (!Signal::isExit() && ($payload = $this->reserve()) !== null) {
            list($id, $message, $ttr, $attempt) = $payload;
            if ($this->handleMessage($id, $message, $ttr, $attempt)) {
                $this->delete($payload);
            }
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
     * Reserves message for execute
     *
     * @return string|null payload
     */
    protected function reserve()
    {
        $id = null;
        $ttr = null;
        $attempt = null;
        $this->touchIndex(function (&$data) use (&$id, &$ttr, &$attempt) {
            if (!empty($data['reserved'])) {
                foreach ($data['reserved'] as $key => $payload) {
                    if ($payload[1] + $payload[3] < time()) {
                        list($id, $ttr, $attempt, $time) = $payload;
                        $data['reserved'][$key][2] = ++$attempt;
                        $data['reserved'][$key][3] = time();
                        return;
                    }
                }
            }

            if (!empty($data['delayed']) && $data['delayed'][0][2] <= time()) {
                list($id, $ttr,) = array_shift($data['delayed']);
            } elseif (!empty($data['waiting'])) {
                list($id, $ttr) = array_shift($data['waiting']);
            }
            if ($id) {
                $attempt = 1;
                $data['reserved']["job$id"] = [$id, $ttr, $attempt, time()];
            }
        });

        if ($id) {
            return [$id, file_get_contents("$this->path/job$id.data"), $ttr, $attempt];
        } else {
            return null;
        }
    }

    /**
     * Deletes reserved message
     *
     * @param array $payload
     */
    protected function delete($payload)
    {
        $id = $payload[0];
        $this->touchIndex(function (&$data) use ($id) {
            foreach ($data['reserved'] as $key => $payload) {
                if ($payload[0] === $id) {
                    unset($data['reserved'][$key]);
                    break;
                }
            }
        });
        unlink("$this->path/job$id.data");
    }

    /**
     * @inheritdoc
     */
    protected function pushMessage($message, $ttr, $delay, $priority)
    {
        if ($priority !== null) {
            throw new NotSupportedException('Job priority is not supported in the driver.');
        }

        $this->touchIndex(function (&$data) use ($message, $ttr, $delay, &$id) {
            if (!isset($data['lastId'])) {
                $data['lastId'] = 0;
            }
            $id = ++$data['lastId'];
            $fileName = "$this->path/job$id.data";
            file_put_contents($fileName, $message);
            if ($this->fileMode !== null) {
                chmod($fileName, $this->fileMode);
            }
            if (!$delay) {
                $data['waiting'][] = [$id, $ttr, 0];
            } else {
                $data['delayed'][] = [$id, $ttr, time() + $delay];
                usort($data['delayed'], function ($a, $b) {
                    if ($a[2] < $b[2]) return -1;
                    if ($a[2] > $b[2]) return 1;
                    if ($a[0] < $b[0]) return -1;
                    if ($a[0] > $b[0]) return 1;
                    return 0;
                });
            }
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
     * @param callable $callback
     * @throws InvalidConfigException
     */
    private function touchIndex($callback)
    {
        $fileName = "$this->path/index.data";
        $isNew = !file_exists($fileName);
        touch($fileName);
        if ($isNew && $this->fileMode !== null) {
            chmod($fileName, $this->fileMode);
        }
        if (($file = fopen($fileName, 'r+')) === false) {
            throw new InvalidConfigException("Unable to open index file: $fileName");
        }
        flock($file, LOCK_EX);
        $content = stream_get_contents($file);
        $data = $content === '' ? [] : unserialize($content);
        try {
            $callback($data);
            $newContent = serialize($data);
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