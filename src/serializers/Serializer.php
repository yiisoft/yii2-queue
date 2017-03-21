<?php

namespace zhuravljov\yii\queue\serializers;

use zhuravljov\yii\queue\Job;

/**
 * Class Serializer
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
interface Serializer
{
    /**
     * @param Job|mixed $job
     * @return string
     */
    public function serialize($job);

    /**
     * @param string $serialized
     * @return Job
     */
    public function unserialize($serialized);
}