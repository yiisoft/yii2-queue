<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\amqp;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use yii\base\Application as BaseApp;
use yii\base\Event;
use yii\base\NotSupportedException;
use zhuravljov\yii\queue\cli\Queue as CliQueue;

/**
 * Amqp Queue
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Queue extends CliQueue
{
    const EXCHANGE_DIRECT = 'direct';
    const EXCHANGE_TOPIC = 'topic';
    const EXCHANGE_FANOUT = 'fanout';

    public $host = 'localhost';
    public $port = 5672;
    public $user = 'guest';
    public $password = 'guest';
    public $queueName = 'queue';
    public $exchangeName = 'exchange';
    public $exchangeType = self::EXCHANGE_DIRECT;

    /**
     * @var string command class name
     */
    public $commandClass = Command::class;

    /**
     * @var AMQPStreamConnection
     */
    private $connection;
    /**
     * @var AMQPChannel
     */
    private $channel;

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
     * Listens amqp-queue and runs new jobs.
     */
    public function listen()
    {
        $this->open();
        $callback = function(AMQPMessage $message) {
            if ($this->handleMessage(null, $message->body)) {
                $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
            }
        };
        $this->channel->basic_qos(null, 1, null);
        $this->channel->basic_consume($this->queueName, '', false, false, false, false, $callback);
        while(count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

    /**
     * @inheritdoc
     */
    protected function pushMessage($message, $delay, $priority)
    {
        if ($delay) {
            throw new NotSupportedException('Delayed work is not supported in the driver.');
        }
        if ($priority !== null) {
            throw new NotSupportedException('Job priority is not supported in the driver.');
        }

        $this->open();
        $this->channel->basic_publish(new AMQPMessage($message), $this->exchangeName);

        return null;
    }

    /**
     * @inheritdoc
     */
    protected function status($id)
    {
        throw new NotSupportedException('Status is not supported in the driver.');
    }

    /**
     * Opens connection and channel
     */
    protected function open()
    {
        if ($this->channel) return;
        $this->connection = new AMQPStreamConnection($this->host, $this->port, $this->user, $this->password);
        $this->channel = $this->connection->channel();
        $this->channel->queue_declare($this->queueName);
        $this->channel->exchange_declare($this->exchangeName, $this->exchangeType, false, true, false);
        $this->channel->queue_bind($this->queueName, $this->exchangeName);
    }

    /**
     * Closes connection and channel
     */
    protected function close()
    {
        if (!$this->channel) return;
        $this->channel->close();
        $this->connection->close();
    }
}