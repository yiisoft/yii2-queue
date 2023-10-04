<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue\stomp;

use Enqueue\Stomp\StompConnectionFactory;
use Enqueue\Stomp\StompContext;
use Enqueue\Stomp\StompDestination;
use Enqueue\Stomp\StompMessage;
use Interop\Queue\Exception as QueueException;
use Interop\Queue\Message;
use Interop\Queue\Queue as InteropQueue;
use yii\base\Application as BaseApp;
use yii\base\Event;
use yii\base\NotSupportedException;
use yii\queue\cli\Queue as CliQueue;

/**
 * Stomp Queue.
 * @author Sergey Vershinin <versh23@gmail.com>
 * @since 2.3.0
 */
class Queue extends CliQueue
{
    public const ATTEMPT = 'yii-attempt';
    public const TTR = 'yii-ttr';

    /**
     * The message queue broker's host.
     *
     * @var string|null
     */
    public ?string $host;
    /**
     * The message queue broker's port.
     *
     * @var string|null
     */
    public ?string $port = null;
    /**
     * This is user which is used to login on the broker.
     *
     * @var string|null
     */
    public ?string $user = null;
    /**
     * This is password which is used to login on the broker.
     *
     * @var string|null
     */
    public ?string $password = null;
    /**
     * Sets an fixed vhost name, which will be passed on connect as header['host'].
     *
     * @var string|null
     */
    public ?string $vhost = null;
    /**
     * @var int
     */
    public int $bufferSize = 1000;
    /**
     * @var int
     */
    public int $connectionTimeout = 1;
    /**
     * Perform request synchronously.
     * @var bool
     */
    public bool $sync = false;
    /**
     * The connection will be established as later as possible if set true.
     *
     * @var bool|null
     */
    public ?bool $lazy = true;
    /**
     * Defines whether secure connection should be used or not.
     *
     * @var bool|null
     */
    public ?bool $sslOn = false;
    /**
     * The queue used to consume messages from.
     *
     * @var string
     */
    public string $queueName = 'stomp_queue';
    /**
     * The property contains a command class which used in cli.
     *
     * @var string command class name
     */
    public string $commandClass = Command::class;
    /**
     * Set the read timeout.
     * @var int
     */
    public int $readTimeOut = 0;

    /**
     * @var StompContext|null
     */
    protected ?StompContext $context = null;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        Event::on(BaseApp::class, BaseApp::EVENT_AFTER_REQUEST, function () {
            $this->close();
        });
    }

    /**
     * Opens connection.
     */
    protected function open(): void
    {
        if ($this->context) {
            return;
        }

        $config = [
            'host' => $this->host,
            'port' => $this->port,
            'login' => $this->user,
            'password' => $this->password,
            'vhost' => $this->vhost,
            'buffer_size' => $this->bufferSize,
            'connection_timeout' => $this->connectionTimeout,
            'sync' => $this->sync,
            'lazy' => $this->lazy,
            'ssl_on' => $this->sslOn,
        ];

        $config = array_filter($config, static function ($value) {
            return null !== $value;
        });

        $factory = new StompConnectionFactory($config);

        $this->context = $factory->createContext();
    }

    /**
     * Listens queue and runs each job.
     *
     * @param bool $repeat
     * @param int $timeout
     * @return int|null
     */
    public function run(bool $repeat, int $timeout = 0): ?int
    {
        return $this->runWorker(function (callable $canContinue) use ($repeat, $timeout) {
            $this->open();
            $queue = $this->createQueue($this->queueName);
            $consumer = $this->context->createConsumer($queue);

            while ($canContinue()) {
                $message = $this->readTimeOut > 0 ? $consumer->receive($this->readTimeOut) : $consumer->receiveNoWait();
                if ($message) {
                    $messageId = $message->getMessageId();
                    if (!$messageId) {
                        $message = $this->setMessageId($message);
                    }

                    if ($message->isRedelivered()) {
                        $consumer->acknowledge($message);

                        $this->redeliver($message);

                        continue;
                    }

                    $ttr = $message->getProperty(self::TTR, $this->ttr);
                    $attempt = $message->getProperty(self::ATTEMPT, 1);

                    if ($this->handleMessage($message->getMessageId(), $message->getBody(), $ttr, $attempt)) {
                        $consumer->acknowledge($message);
                    } else {
                        $consumer->acknowledge($message);

                        $this->redeliver($message);
                    }
                } elseif (!$repeat) {
                    break;
                } elseif ($timeout) {
                    sleep($timeout);
                    $this->context->getStomp()->getConnection()?->sendAlive();
                }
            }
        });
    }

    /**
     * @param StompMessage $message
     * @return StompMessage
     */
    protected function setMessageId(Message $message): StompMessage
    {
        $message->setMessageId(uniqid('', true));
        return $message;
    }

    /**
     * @inheritdoc
     * @throws QueueException
     * @throws NotSupportedException
     */
    protected function pushMessage(string $payload, int $ttr, int $delay, mixed $priority): int|string|null
    {
        $this->open();

        $queue = $this->createQueue($this->queueName);
        $message = $this->context->createMessage($payload);
        $message = $this->setMessageId($message);
        $message->setPersistent(true);
        $message->setProperty(self::ATTEMPT, 1);
        $message->setProperty(self::TTR, $ttr);

        $producer = $this->context->createProducer();

        if ($delay) {
            throw new NotSupportedException('Delayed work is not supported in the driver.');
        }

        if ($priority) {
            throw new NotSupportedException('Job priority is not supported in the driver.');
        }

        $producer->send($queue, $message);

        return $message->getMessageId();
    }

    /**
     * Closes connection.
     */
    protected function close(): void
    {
        if (!$this->context) {
            return;
        }

        $this->context->close();
        $this->context = null;
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException
     */
    public function status($id): int
    {
        throw new NotSupportedException('Status is not supported in the driver.');
    }

    /**
     * @param StompMessage $message
     * @throws QueueException
     */
    protected function redeliver(StompMessage $message): void
    {
        $attempt = $message->getProperty(self::ATTEMPT, 1);

        $newMessage = $this->context->createMessage(
            $message->getBody(),
            $message->getProperties(),
            $message->getHeaders()
        );
        $newMessage->setProperty(self::ATTEMPT, ++$attempt);

        $this->context->createProducer()->send(
            $this->createQueue($this->queueName),
            $newMessage
        );
    }

    /**
     * @param string $name
     * @return InteropQueue|StompDestination
     */
    private function createQueue(string $name): InteropQueue|StompDestination
    {
        $queue = $this->context->createQueue($name);
        $queue->setDurable(true);
        $queue->setAutoDelete(false);
        $queue->setExclusive(false);

        return $queue;
    }
}
