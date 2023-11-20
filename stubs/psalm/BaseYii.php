<?php
declare(strict_types=1);

namespace psalm;

use yii;

class BaseYii
{
    /**
     * @template T
     * @param  class-string<T>|array{class: class-string<T>}|callable(): T $type
     * @param array<mixed> $params
     *
     * @return T
     */
    abstract public static function createObject($type, array $params = []);
}
