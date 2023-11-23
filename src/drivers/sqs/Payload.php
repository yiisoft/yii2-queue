<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue\sqs;

class Payload
{
    public array $messages = [];
    public int $ttr = 0;
    public mixed $receiptHandle = null;
    public string $body = '';
    public string|int $messageId = '';
    public int $attempt = 0;

    /**
     * @param array $response
     * @psalm-suppress MixedArrayAccess, MixedArgument
     */
    public function __construct(array $response)
    {
        $this->messages = $response['Messages']??[];
        if (!empty($this->messages)) {
            $messages = $this->messages;
            $message = reset($messages);

            $this->ttr = (int)$message['MessageAttributes']['TTR']['StringValue'];
            $this->body = $message['Body'];
            $this->messageId = $message['MessageId'];
            $this->attempt = (int)$message['Attributes']['ApproximateReceiveCount'];
            $this->receiptHandle = $message['ReceiptHandle'];
        }
    }
}
