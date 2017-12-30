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
use yii\queue\cli\LoopInterface;
use yii\queue\cli\Queue as CliQueue;

/**
 * File Queue
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Queue extends CliQueue
{
    /**
     * @var string
     */
    public $path = '@runtime/queue';
    /**
     * @var int
     */
    public $dirMode = 0755;
    /**
     * @var int|null
     */
    public $fileMode;
    /**
     * @var callable
     */
    public $indexSerializer = 'serialize';
    /**
     * @var callable
     */
    public $indexDeserializer = 'unserialize';
    /**
     * @var string
     */
    public $commandClass = Command::class;


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->path = Yii::getAlias($this->path);
        if (!is_dir($this->path)) {
            FileHelper::createDirectory($this->path, $this->dirMode, true);
        }
    }

    /**
     * Listens queue and runs each job.
     *
     * @param bool $repeat whether to continue listening when queue is empty.
     * @param int $delay number of seconds to sleep before next iteration.
     * @return null|int exit code.
     * @internal for worker command only.
     * @since 2.0.2
     */
    public function run($repeat, $delay = 0)
    {
        return $this->runWorker(function (LoopInterface $loop) use ($repeat, $delay) {
            while ($loop->canContinue()) {
                if (($payload = $this->reserve()) !== null) {
                    list($id, $message, $ttr, $attempt) = $payload;
                    if ($this->handleMessage($id, $message, $ttr, $attempt)) {
                        $this->delete($payload);
                    }
                } elseif (!$repeat) {
                    break;
                } elseif ($delay) {
                    sleep($delay);
                }
            }
        });
    }

    /**
     * @inheritdoc
     */
    public function status($id)
    {
        if (!is_numeric($id) || $id <= 0) {
            throw new InvalidParamException("Unknown message ID: $id.");
        }

        if (file_exists("$this->path/job$id.data")) {
            return self::STATUS_WAITING;
        }

        return self::STATUS_DONE;
    }

    /**
     * Clears the queue
     *
     * @since 2.0.1
     */
    public function clear()
    {
        $this->touchIndex(function (&$data) {
            $data = [];
            foreach (glob("$this->path/job*.data") as $fileName) {
                unlink($fileName);
            }
        });
    }

    /**
     * Removes a job by ID
     *
     * @param int $id of a job
     * @return bool
     * @since 2.0.1
     */
    public function remove($id)
    {
        $removed = false;
        $this->touchIndex(function (&$data) use ($id, &$removed) {
            if (!empty($data['waiting'])) {
                foreach ($data['waiting'] as $key => $payload) {
                    if ($payload[0] === $id) {
                        unset($data['waiting'][$key]);
                        $removed = true;
                        break;
                    }
                }
            }
            if (!$removed && !empty($data['delayed'])) {
                foreach ($data['delayed'] as $key => $payload) {
                    if ($payload[0] === $id) {
                        unset($data['delayed'][$key]);
                        $removed = true;
                        break;
                    }
                }
            }
            if (!$removed && !empty($data['reserved'])) {
                foreach ($data['reserved'] as $key => $payload) {
                    if ($payload[0] === $id) {
                        unset($data['reserved'][$key]);
                        $removed = true;
                        break;
                    }
                }
            }
            if ($removed) {
                unlink("$this->path/job$id.data");
            }
        });

        return $removed;
    }

    /**
     * Reserves message for execute
     *
     * @return array|null payload
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
        }

        return null;
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
        if (($file = fopen($fileName, 'rb+')) === false) {
            throw new InvalidConfigException("Unable to open index file: $fileName");
        }
        flock($file, LOCK_EX);
        $data = [];
        $content = stream_get_contents($file);
        if ($content !== '') {
            $data = call_user_func($this->indexDeserializer, $content);
        }
        try {
            $callback($data);
            $newContent = call_user_func($this->indexSerializer, $data);
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