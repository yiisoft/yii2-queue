<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace tests\serializers;

use yii\base\BaseObject;

class TestObject extends BaseObject
{
    public int $foo;
    /** @var array<int, mixed> $bar */
    public array $bar = [];
}
