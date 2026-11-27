<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace tests\cli;

use tests\app\SimpleJob;
use tests\TestCase;
use yii\base\Module;
use yii\console\Controller;
use yii\helpers\BaseConsole;
use yii\queue\cli\VerboseBehavior;
use yii\queue\ExecEvent;

/**
 * Verbose Behavior Test.
 */
final class VerboseBehaviorTest extends TestCase
{
    public function testBeforeExecPrintsStarted(): void
    {
        $output = [];
        $controller = $this->getMockBuilder(Controller::class)
            ->setConstructorArgs(['testController', new Module('testModule')])
            ->onlyMethods(['stdout'])
            ->getMock();
        $controller
            ->expects($this->atLeastOnce())
            ->method('stdout')
            ->willReturnCallback(static function (string $string, int ...$args) use (&$output): int {
                $output[] = [$string, $args];
                return strlen($string);
            });

        $behavior = new VerboseBehavior();
        $behavior->command = $controller;

        $event = new ExecEvent([
            'sender' => new Queue(),
            'id' => 42,
            'job' => new SimpleJob(),
            'attempt' => 1,
        ]);

        $behavior->beforeExec($event);

        $text = implode('', array_column($output, 0));
        $this->assertStringContainsString('[42] ' . SimpleJob::class . ' (attempt: 1', $text);
        $this->assertStringContainsString(' - Started', $text);
        $this->assertStringEndsWith(PHP_EOL, $text);
        $this->assertContains(['Started', [BaseConsole::FG_GREEN]], $output);
    }
}
