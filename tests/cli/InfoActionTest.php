<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace tests\cli;

use tests\cli\providers\BaseStatisticsProvider;
use tests\TestCase;
use yii\base\Module;
use yii\console\Controller;
use yii\helpers\BaseConsole;
use yii\queue\cli\InfoAction;

/**
 * Info Action Test.
 *
 * @author Kalmer Kaurson <kalmerkaurson@gmail.com>
 */
final class InfoActionTest extends TestCase
{
    public function testWaitingCount(): void
    {
        $controller = $this->getMockBuilder(Controller::class)
            ->setConstructorArgs(['testController', new Module('testModule')])
            ->getMock();

        $controller
            ->expects($this->exactly(9))
            ->method('stdout')
            ->willReturnOnConsecutiveCalls(
                [
                    'Jobs' . PHP_EOL,
                    BaseConsole::FG_GREEN,
                ],
                [
                    '- waiting: ',
                    BaseConsole::FG_YELLOW,
                ],
                [
                    10 . PHP_EOL,
                ],
                [
                    '- delayed: ',
                    BaseConsole::FG_YELLOW,
                ],
                [
                    0 . PHP_EOL,
                    null
                ],
                [
                    '- reserved: ',
                    BaseConsole::FG_YELLOW,
                ],
                [
                    0 . PHP_EOL,
                    null,
                ],
                [
                    '- done: ',
                    BaseConsole::FG_YELLOW,
                ],
                [
                    0 . PHP_EOL,
                    null,
                ],
            );

        $queue = $this->getMockBuilder(Queue::class)->getMock();

        $provider = $this->getMockBuilder(BaseStatisticsProvider::class)
            ->onlyMethods(['getWaitingCount'])
            ->setConstructorArgs([$queue])
            ->getMock();
        $provider->expects($this->once())
            ->method('getWaitingCount')
            ->willReturn(10);

        $queue
            ->expects($this->once())
            ->method('getStatisticsProvider')
            ->willReturn($provider);

        (new InfoAction('infoAction', $controller, [
            'queue' => $queue,
        ]))->run();
    }

    public function testDelayedCount(): void
    {
        $controller = $this->getMockBuilder(Controller::class)
            ->setConstructorArgs(['testController', new Module('testModule')])
            ->getMock();

        $controller
            ->expects($this->exactly(9))
            ->method('stdout')
            ->willReturnOnConsecutiveCalls(
                [
                    'Jobs' . PHP_EOL,
                    BaseConsole::FG_GREEN,
                ],
                [
                    '- waiting: ',
                    BaseConsole::FG_YELLOW,
                ],
                [
                    0 . PHP_EOL,
                    null,
                ],
                [
                    '- delayed: ',
                    BaseConsole::FG_YELLOW,
                ],
                [
                    10 . PHP_EOL,
                    null,
                ],
                [
                    '- reserved: ',
                    BaseConsole::FG_YELLOW,
                ],
                [
                    0 . PHP_EOL,
                    null,
                ],
                [
                    '- done: ',
                    BaseConsole::FG_YELLOW,
                ],
                [
                    0 . PHP_EOL,
                    null,
                ],
            );

        $queue = $this->getMockBuilder(Queue::class)->getMock();

        $provider = $this->getMockBuilder(BaseStatisticsProvider::class)
            ->onlyMethods(['getDelayedCount'])
            ->setConstructorArgs([$queue])
            ->getMock();
        $provider->expects($this->once())
            ->method('getDelayedCount')
            ->willReturn(10);

        $queue
            ->expects($this->once())
            ->method('getStatisticsProvider')
            ->willReturn($provider);

        (new InfoAction('infoAction', $controller, [
            'queue' => $queue,
        ]))->run();
    }

    public function testReservedCount(): void
    {
        $controller = $this->getMockBuilder(Controller::class)
            ->setConstructorArgs(['testController', new Module('testModule')])
            ->getMock();

        $controller
            ->expects($this->exactly(9))
            ->method('stdout')
            ->willReturnOnConsecutiveCalls(
                [
                    'Jobs' . PHP_EOL,
                    BaseConsole::FG_GREEN,
                ],
                [
                    '- waiting: ',
                    BaseConsole::FG_YELLOW,
                ],
                [
                    0 . PHP_EOL,
                    null,
                ],
                [
                    '- delayed: ',
                    BaseConsole::FG_YELLOW
                ],
                [
                    0 . PHP_EOL,
                    null,
                ],
                [
                    '- reserved: ',
                    BaseConsole::FG_YELLOW,
                ],
                [
                    10 . PHP_EOL,
                    null,
                ],
                [
                    '- done: ',
                    BaseConsole::FG_YELLOW,
                ],
                [
                    0 . PHP_EOL,
                    null,
                ],
            );

        $queue = $this->getMockBuilder(Queue::class)->getMock();

        $provider = $this->getMockBuilder(BaseStatisticsProvider::class)
            ->onlyMethods(['getReservedCount'])
            ->setConstructorArgs([$queue])
            ->getMock();
        $provider->expects($this->once())
            ->method('getReservedCount')
            ->willReturn(10);

        $queue
            ->expects($this->once())
            ->method('getStatisticsProvider')
            ->willReturn($provider);

        (new InfoAction('infoAction', $controller, [
            'queue' => $queue,
        ]))->run();
    }

    public function testDoneCount(): void
    {
        $controller = $this->getMockBuilder(Controller::class)
            ->setConstructorArgs(['testController', new Module('testModule')])
            ->getMock();

        $controller
            ->expects($this->exactly(9))
            ->method('stdout')
            ->willReturnOnConsecutiveCalls(
                [
                    'Jobs' . PHP_EOL,
                    BaseConsole::FG_GREEN,
                ],
                [
                    '- waiting: ',
                    BaseConsole::FG_YELLOW,
                ],
                [
                    0 . PHP_EOL,
                    null,
                ],
                [
                    '- delayed: ',
                    BaseConsole::FG_YELLOW,
                ],
                [
                    0 . PHP_EOL,
                    null,
                ],
                [
                    '- reserved: ',
                    BaseConsole::FG_YELLOW,
                ],
                [
                    0 . PHP_EOL,
                    null,
                ],
                [
                    '- done: ',
                    BaseConsole::FG_YELLOW,
                ],
                [
                    10 . PHP_EOL,
                    null,
                ],
            );

        $queue = $this->getMockBuilder(Queue::class)->getMock();

        $provider = $this->getMockBuilder(BaseStatisticsProvider::class)
            ->onlyMethods(['getDoneCount'])
            ->setConstructorArgs([$queue])
            ->getMock();
        $provider->expects($this->once())
            ->method('getDoneCount')
            ->willReturn(10);

        $queue
            ->expects($this->once())
            ->method('getStatisticsProvider')
            ->willReturn($provider);

        (new InfoAction('infoAction', $controller, [
            'queue' => $queue,
        ]))->run();
    }
}
