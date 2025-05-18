<?php

declare(strict_types=1);

namespace tests\drivers\redis;

use yii\redis\Connection;

class RedisCrashMock extends Connection
{
    public $crashOnCommand;

    public function executeCommand($name, $params = [])
    {
        if ($name === $this->crashOnCommand) {
            throw new \RuntimeException('Simulated Redis crash');
        }
        return parent::executeCommand($name, $params);
    }
}
