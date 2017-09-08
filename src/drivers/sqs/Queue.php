<?php

namespace yii\queue\sqs;

use yii\base\NotSupportedException;
use yii\queue\cli\Queue as CliQueue;
use yii\queue\cli\Signal;
use \Aws\Sqs\SqsClient;

/**
 * SQS Queue
 *
 * @author Manoj Malviya <manojm@girnarsoft.com>
 */
class Queue extends CliQueue
{
    /**
     * The SQS url.
     * @var string
     */
    public $url;

    public $key = '';
    public $secret = '';
    public $region = '';
    public $version = 'latest';

    /**
     * @var string command class name
     */
    public $commandClass = Command::class;

    /**
     * @inheritdoc
     */
     public function init()
     {
         parent::init();
     }

    /**
     * Runs all jobs from gearman-queue.
     */
    public function run()
    {
        while (!Signal::isExit() && ($payload = $this->getPayload())) {
            list($ttr, $message) = explode(';', $payload->workload(), 2);
            if($this->handleMessage(null, $message, $ttr, 1))
            {
                $this->release($payload);
            }
        }
    }

    /**
     * Listens to get new jobs.
     */
    public function listen()
    {
        $this->run();        
    }

    /**
     * @inheritdoc
     */
    protected function pushMessage($message, $ttr, $delay, $priority)
    {
        if ($priority)
        {
            throw new NotSupportedException('Priority is not supported in this driver');
        }

        $model = $this->getClient()->sendMessage([
            'DelaySeconds' => $delay,
            'QueueUrl' => $this->url,
            'MessageBody' => "$ttr;message",
        ]);

        if ($model !== null) {
            return $model['MessageId'];
        } else {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function status($id)
    {
        $status = $this->getClient()->jobStatus($id);
        if ($status[0] && !$status[1]) {
            return self::STATUS_WAITING;
        } elseif ($status[0] && $status[1]) {
            return self::STATUS_RESERVED;
        } else {
            return self::STATUS_DONE;
        }
    }

    /**
     * @return \Aws\Sqs\SqsClient
     */
    protected function getClient()
    {
        if (!$this->_client) {
            $this->_client = SqsClient::factory($this->config);

        }
        return $this->_client;
    }

    private $_client;

    private function getPayload()
    {
        $payload = $this->getClient()->receiveMessage([
            'QueueUrl' => $this->url,
            'AttributeNames' => ['ApproximateReceiveCount'],
            'MaxNumberOfMessages' => 1,
        ]);

        return $payload;
    }

    private function release()
    {
        if (!empty($job->header['ReceiptHandle'])) {
            $receiptHandle = $job->header['ReceiptHandle'];
            $response = $this->getClient()->deleteMessage([
                'QueueUrl'      => $this->url,
                'ReceiptHandle' => $receiptHandle,
            ]);

            return $response !== null;
        }

        return false;
    }
}