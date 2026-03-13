<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace yii\queue\closure;

use Laravel\SerializableClosure\SerializableClosure;
use Laravel\SerializableClosure\Serializers\Native;
use yii\queue\JobInterface;
use yii\queue\Queue;

/**
 * Closure Job.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Job implements JobInterface
{
    /**
     * @var string serialized closure
     */
    public string $serialized = '';

    /**
     * Unserializes and executes a closure.
     * @inheritdoc
     */
    public function execute(Queue $queue)
    {
        /** @var SerializableClosure $unserialize */
        $unserialize = unserialize($this->serialized);
        $closure = $unserialize->getClosure();
        $nativeClosure = $closure();

        if ($nativeClosure instanceof Native) {
            return $nativeClosure();
        }

        /** @var JobInterface $nativeClosure */
        return $nativeClosure->execute($queue);
    }
}
