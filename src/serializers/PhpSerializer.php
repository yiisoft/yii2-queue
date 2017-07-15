<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\serializers;

use yii\base\Object;

/**
 * Class PhpSerializer
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class PhpSerializer extends Object implements Serializer
{
    /**
     * @inheritdoc
     */
    public function serialize($job)
    {
        return serialize($job);
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        return unserialize($serialized);
    }
}