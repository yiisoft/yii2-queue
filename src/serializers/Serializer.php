<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

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