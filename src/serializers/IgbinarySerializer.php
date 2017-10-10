<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */
namespace yii\queue\serializers;

use yii\base\Object;

/**
 * Class IgbinarySerializer
 *
 * @author xutl <xutongle@gmail.com>
 */
class IgbinarySerializer extends Object implements SerializerInterface
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
