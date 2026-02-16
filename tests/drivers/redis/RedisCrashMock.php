<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace tests\drivers\redis;

use RuntimeException;
use yii\redis\Connection;

class RedisCrashMock extends Connection
{
    public $crashOnCommand;

    public function executeCommand($name, $params = []): bool|array|string|null
    {
        if ($name === $this->crashOnCommand) {
            throw new RuntimeException('Simulated Redis crash');
        }
        return parent::executeCommand($name, $params);
    }
}
