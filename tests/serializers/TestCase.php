<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tests\serializers;

use tests\app\SimpleJob;
use yii\base\BaseObject;
use yii\queue\serializers\SerializerInterface;

/**
 * Serializer Test Case.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class TestCase extends \tests\TestCase
{
    /**
     * @return SerializerInterface
     */
    abstract protected function createSerializer();

    /**
     * @dataProvider providerSerialize
     * @param mixed $expected
     */
    public function testSerialize($expected)
    {
        $serializer = $this->createSerializer();

        $serialized = $serializer->serialize($expected);
        $actual = $serializer->unserialize($serialized);

        $this->assertEquals($expected, $actual, "Payload: $serialized");
    }

    public function providerSerialize()
    {
        return [
            // Job object
            [
                new SimpleJob(['uid' => 123])
            ],
            // Any object
            [
                new TestObject([
                    'foo' => 1,
                    'bar' => [
                        new TestObject(['foo' => 1]),
                    ]
                ]),
            ],
            // Array of mixed data
            [
                [
                    'a' => 'b',
                    'c' => [
                        222,
                        new TestObject(),
                    ],
                    'd' => [
                        new TestObject(),
                    ],
                ],
            ],
            // Scalar
            [
                'string value'
            ],
        ];
    }
}

class TestObject extends BaseObject
{
    public $foo;
    public $bar;
}
