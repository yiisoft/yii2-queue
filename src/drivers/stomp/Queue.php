<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue\stomp;

use Enqueue\Stomp\StompConnectionFactory;
use Enqueue\Stomp\StompContext;
use Enqueue\Stomp\StompMessage;
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
    const ATTEMPT = 'yii-attempt';
    const TTR = 'yii-ttr';

    /**
     * The message queue broker's host.
     *
     * @var string|null
     */
    public $host;
    /**
     * The message queue broker's port.
     *
     * @var string|null
     */
    public $port;
    /**
     * This is user which is used to login on the broker.
     *
     * @var string|null
     */
    public $user;
    /**
     * This is password which is used to login on the broker.
     *
     * @var string|null
     */
    public $password;
    /**
     * Sets an fixed vhostname, which will be passed on connect as header['host'].
     *
     * @var string|null
     */
    public $vhost;
    /**
     * @var int
     */
    public $bufferSize;
    /**
     * @var int
     */
    public $connectionTimeout;
    /**
     * Perform request synchronously.
     * @var bool
     */
    public $sync;
    /**
     * The connection will be established as later as possible if set true.
     *
     * @var bool|null
     */
    public $lazy;
    /**
     * Defines whether secure connection should be used or not.
     *
     * @var bool|null
     */
    public $sslOn;
    /**
     * The queue used to consume messages from.
     *
     * @var string
     */
    public $queueName = 'stomp_queue';
    /**
     * The property contains a command class which used in cli.
     *
     * @var string command class name
     */
    public $commandClass = Command::class;
    /**
     * Set the read timeout.
     * @var int
     */
    public $readTimeOut = 0;

    /**
     * @var StompContext
     */
    protected $context;


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        Event::on(BaseApp::class, BaseApp::EVENT_AFTER_REQUEST, function () {
            $this->close();
        });
    }

    /**
     * Opens connection.
     */
    protected function open()
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

        $config = array_filter($config, function ($value) {
            return null !== $value;
        });

        $factory = new StompConnectionFactory($config);

        $this->context = $factory->createContext();
    }

    /**
     * Listens queue and runs each job.
     *
     * @param $repeat
     * @param int $timeout
     * @return int|null
     */
    public function run($repeat, $timeout = 0)
    {
        return $this->runWorker(function (callable $canContinue) use ($repeat, $timeout) {
            $this->open();
            $queue = $this->createQueue($this->queueName);
            $consumer = $this->context->createConsumer($queue);

            while ($canContinue()) {
                if ($message = ($this->readTimeOut > 0 ? $consumer->receive($this->readTimeOut) : $consumer->receiveNoWait())) {
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
                    $this->context->getStomp()->getConnection()->sendAlive();
                }
            }
        });
    }

    /**
     * @param StompMessage $message
     * @return StompMessage
     * @throws \Interop\Queue\Exception
     */
    protected function setMessageId(StompMessage $message)
    {
        $message->setMessageId(uniqid('', true));
        return $message;
    }

    /**
     * @inheritdoc
     * @throws \Interop\Queue\Exception
     * @throws NotSupportedException
     */
    protected function pushMessage($message, $ttr, $delay, $priority)
    {
        $this->open();

        $queue = $this->createQueue($this->queueName);
        $message = $this->context->createMessage($message);
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
    protected function close()
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
    public function status($id)
    {
        throw new NotSupportedException('Status is not supported in the driver.');
    }

    /**
     * @param StompMessage $message
     * @throws \Interop\Queue\Exception
     */
    protected function redeliver(StompMessage $message)
    {
        $attempt = $message->getProperty(self::ATTEMPT, 1);

        $newMessage = $this->context->createMessage($message->getBody(), $message->getProperties(), $message->getHeaders());
        $newMessage->setProperty(self::ATTEMPT, ++$attempt);

        $this->context->createProducer()->send(
            $this->createQueue($this->queueName),
            $newMessage
        );
    }

    /**
     * @param $name
     * @return \Enqueue\Stomp\StompDestination
     */
    private function createQueue($name)
    {
        $queue = $this->context->createQueue($name);
        $queue->setDurable(true);
        $queue->setAutoDelete(false);
        $queue->setExclusive(false);

        return $queue;
    }
}
