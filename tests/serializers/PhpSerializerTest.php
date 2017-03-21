<?php

namespace tests\serializers;

use zhuravljov\yii\queue\serializers\PhpSerializer;

/**
 * Class PhpSerializerTest
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class PhpSerializerTest extends TestCase
{
    protected function createSerializer()
    {
        return new PhpSerializer();
    }
}