<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace tests\serializers;

use yii\base\InvalidConfigException;
use yii\queue\serializers\JsonSerializer;
use yii\queue\serializers\SerializerInterface;

/**
 * Json Serializer Test.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
final class JsonSerializerTest extends TestCase
{
    protected function createSerializer(): SerializerInterface
    {
        return new JsonSerializer();
    }

    public function testInvalidArrayKey(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->createSerializer()->serialize([
            'class' => 'failed param',
        ]);
    }
}
