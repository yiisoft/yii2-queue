<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\serializers;

use yii\queue\Job;

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