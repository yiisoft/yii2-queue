<?php

namespace yii\queue\queue_interop;

use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrMessage;

interface Driver
{
    const H_ATTEMPT = 'yii-attempt';
    const H_TTR = 'yii-ttr';
    const H_DELAY = 'yii-delay';
    const H_PRIORITY = 'yii-priority';

    /**
     * @param string $message
     * @param string $ttr
     * @param int    $delay
     * @param int    $priority
     */
    public function push($message, $ttr, $delay, $priority);

    /**
     * @param PsrMessage $message
     */
    public function redeliver(PsrMessage $message);

    /**
     * Creates all required queues, topics etc
     */
    public function setupBroker();

    /**
     * @return PsrConsumer
     */
    public function getConsumer();
}
