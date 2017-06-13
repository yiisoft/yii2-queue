<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

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