<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue\serializers;

use yii\base\BaseObject;

/**
 * Igbinary Serializer.
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
    public function serialize($job): string
    {
        /** @var string|null|false $serialize */
        $serialize = igbinary_serialize($job);
        return is_string($serialize) ? $serialize : '';
    }

    /**
     * @inheritdoc
     */
    public function unserialize(string $serialized): mixed
    {
        return igbinary_unserialize($serialized);
    }
}
