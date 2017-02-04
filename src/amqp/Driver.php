<?php

namespace zhuravljov\yii\queue\amqp;

use Yii;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\helpers\Inflector;
use zhuravljov\yii\queue\Driver as BaseDriver;
use zhuravljov\yii\queue\Signal;

/**
 * AMQP Driver
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Driver extends BaseDriver implements BootstrapInterface
{
    public $host = 'localhost';
    public $port = 5672;
    public $user = 'guest';
    public $password = 'guest';
    public $queueName = 'queue';

    /**
     * @var AMQPStreamConnection
     */
    private $_connection;
    /**
     * @var AMQPChannel
     */
    private $_channel;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        Yii::$app->on(Application::EVENT_AFTER_REQUEST, function () {
            $this->close();
        });
    }

    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        if ($app instanceof \yii\console\Application) {
            $app->controllerMap[Inflector::camel2id($this->queue->id)] = [
                'class' => Command::class,
                'driver' => $this,
            ];
        }
    }

    /**
     * @inheritdoc
     */
    public function push($job)
    {
        $this->open();
        $message = new AMQPMessage($this->serialize($job));
        $this->_channel->basic_publish($message, '', $this->queueName);
    }

    /**
     * Listens amqp-queue and runs new jobs.
     */
    public function listen()
    {
        $this->open();
        $callback = function(AMQPMessage $message) {
            $job = $this->unserialize($message->body);
            if ($this->getQueue()->run($job)) {
                $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
            }
        };
        $this->_channel->basic_qos(null, 1, null);
        $this->_channel->basic_consume($this->queueName, '', false, false, false, false, $callback);
        while(!Signal::isTerm() && count($this->_channel->callbacks)) {
            $this->_channel->wait();
        }
    }

    /**
     * Opens connection and channel
     */
    protected function open()
    {
        if ($this->_channel) return;
        $this->_connection = new AMQPStreamConnection($this->host, $this->port, $this->user, $this->password);
        $this->_channel = $this->_connection->channel();
        $this->_channel->queue_declare($this->queueName);
    }

    /**
     * Closes connection and channel
     */
    protected function close()
    {
        if (!$this->_channel) return;
        $this->_channel->close();
        $this->_connection->close();
    }
}