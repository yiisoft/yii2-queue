<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace tests\serializers;

use yii\queue\serializers\PhpSerializer;

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