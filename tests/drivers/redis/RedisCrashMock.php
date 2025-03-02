<?php

namespace tests\drivers\redis;

use yii\redis\Connection;

class RedisCrashMock extends Connection
{
    function rpush($key, $value)
    {
        throw new \Exception('panic');
    }
}
