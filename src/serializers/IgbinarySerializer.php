<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\serializers;

use yii\base\BaseObject;

/**
 * Class IgbinarySerializer
 *
 * It uses an alternative serializer available via PECL extension which produces
 * more compact data chunks significantly faster that native PHP one.
 *
 * @author xutl <xutongle@gmail.com>
 */
class IgbinarySerializer extends BaseObject implements SerializerInterface
{
    /**
     * @inheritdoc
     */
    public function serialize($job)
    {
        return igbinary_serialize($job);
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        return igbinary_unserialize($serialized);
    }
}
