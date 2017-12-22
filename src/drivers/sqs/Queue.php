<?php

namespace yii\queue\sqs;

use yii\base\NotSupportedException;
use yii\queue\cli\Queue as CliQueue;
use yii\queue\cli\Signal;
use \Aws\Sqs\SqsClient;
use Aws\Credentials\CredentialProvider;

/**
 * SQS Queue
 *
 * @author Manoj Malviya <manojm@girnarsoft.com>
 */
class Queue extends CliQueue
{
    /**
    * @var SqsClient
    */
    private $_client;

    /**
     * The SQS url.
     * @var string
     */
    public $url;

    /**
     * aws access key
     * @var string
     */
    public $key = '';

    /**
     * aws secret
     * @var string
     */
    public $secret = '';

    /**
     * region where queue is hosted.
     * @var string
     */
    public $region = '';

    /**
     * API version
     * @var string
     */
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
     * Runs all jobs from queue.
     */
    public function run()
    {
        while (!Signal::isExit() && ($payload = $this->getPayload())) {
            list($ttr, $message) = explode(';', $payload['Body'], 2);

            $this->reserve($payload, $ttr); //reserve it so it is not visible to another worker till ttr

            if ($this->handleMessage(null, $message, $ttr, 1)) {
                //if handled then remove from queue
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
        if ($priority) {
            throw new NotSupportedException('Priority is not supported in this driver');
        }

        $model = $this->getClient()->sendMessage([
            'DelaySeconds' => $delay,
            'QueueUrl' => $this->url,
            'MessageBody' => "$ttr;$message",
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
        throw new NotSupportedException('Status is not supported in the driver.');
    }

    /**
     * @return \Aws\Sqs\SqsClient
     */
    protected function getClient()
    {
        if ($this->key && $this->secret) {
            $provider = [
                'key'    => $this->key, 
                'secret' => $this->secret
            ];
        } else {
            // use default provider if no key and secret passed
            //see - http://docs.aws.amazon.com/aws-sdk-php/v3/guide/guide/credentials.html#credential-profiles
            $provider = CredentialProvider::defaultProvider();
        }
        
        $config = [
            'credentials' => $provider,
            'region' => $this->region,
            'version' => $this->version,
        ];

        if (!$this->_client) {
            $this->_client = SqsClient::factory($config);

        }
        return $this->_client;
    }

    /**
    *
    */
    private function getPayload()
    {
        $payload = $this->getClient()->receiveMessage([
            'QueueUrl' => $this->url,
            'AttributeNames' => ['ApproximateReceiveCount'],
            'MaxNumberOfMessages' => 1,
        ]);

        $payload = $payload['Messages'];
        if ($payload) {
            return array_pop($payload);
        }

        return null;
    }

    /**
    * Set the visibility to reserve message
    * So that no other worker can see this message
    *
    * @param array $payload
    * @param int $ttr  
    */
    private function reserve($payload, $ttr)
    {
        $receiptHandle = $payload['ReceiptHandle'];
        $this->getClient()->changeMessageVisibility(array(
            'QueueUrl' => $this->url,
            'ReceiptHandle' => $receiptHandle,
            'VisibilityTimeout' => $ttr
        ));
    }

    /**
    * Mark the message as handled
    *
    * @param array $payload
    * @return boolean
    */
    private function release($payload)
    {
        if (!empty($payload['ReceiptHandle'])) {
            $receiptHandle = $payload['ReceiptHandle'];
            $response = $this->getClient()->deleteMessage([
                'QueueUrl'      => $this->url,
                'ReceiptHandle' => $receiptHandle,
            ]);

            return $response !== null;
        }

        return false;
    }
}