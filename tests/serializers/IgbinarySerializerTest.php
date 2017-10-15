<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tests\serializers;

use yii\queue\serializers\IgbinarySerializer;

/**
 * Class IgbinarySerializerTest
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class IgbinarySerializerTest extends TestCase
{
    protected function createSerializer()
    {
        return new IgbinarySerializer();
    }
}