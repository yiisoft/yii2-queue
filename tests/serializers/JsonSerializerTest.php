<?php

namespace tests\serializers;

use zhuravljov\yii\queue\serializers\JsonSerializer;

/**
 * Class JsonSerializerTest
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class JsonSerializerTest extends TestCase
{
    /**
     * @inheritdoc
     */
    protected function createSerializer()
    {
        return new JsonSerializer();
    }
}