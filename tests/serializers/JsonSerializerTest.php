<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tests\serializers;

use yii\queue\serializers\JsonSerializer;

/**
 * Json Serializer Test.
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

    /**
     * @expectedException \yii\base\InvalidConfigException
     */
    public function testInvalidArrayKey()
    {
        $this->createSerializer()->serialize([
            'class' => 'failed param',
        ]);
    }
}
