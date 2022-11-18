<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue\serializers;

use yii\queue\JobInterface;

/**
 * Serializer Interface.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
interface SerializerInterface
{
    /**
     * @param JobInterface|mixed $job
     * @return string
     */
    public function serialize($job);

    /**
     * @param string $serialized
     * @return JobInterface
     */
    public function unserialize($serialized);
}
