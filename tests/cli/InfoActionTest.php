<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace tests\cli;

use tests\cli\providers\DelayedCountProvider;
use tests\cli\providers\DoneCountProvider;
use tests\cli\providers\ReservedCountProvider;
use tests\cli\providers\WaitingCountProvider;
use tests\TestCase;
use yii\base\Module;
use yii\console\Controller;
use yii\helpers\Console;
use yii\queue\cli\InfoAction;

/**
 * Info Action Test.
 *
 * @author Kalmer Kaurson <kalmerkaurson@gmail.com>
 */
class InfoActionTest extends TestCase
{
    public function testWaitingCount()
    {
        $controller = $this->getMockBuilder(Controller::class)
            ->setConstructorArgs(['testController', new Module('testModule')])
            ->getMock()
        ;

        $controller->expects(self::exactly(3))
            ->method('stdout')
            ->withConsecutive(
                [
                    'Jobs' . PHP_EOL,
                    Console::FG_GREEN,
                ],
                [
                    '- waiting: ',
                    Console::FG_YELLOW,
                ],
                [
                    10 . PHP_EOL
                ]
            )
        ;

        $queue = $this->getMockBuilder(Queue::class)->getMock();

        $provider = $this->getMockBuilder(WaitingCountProvider::class)
            ->setConstructorArgs([$queue])
            ->getMock()
        ;
        $provider->expects(self::once())
            ->method('getWaitingCount')
            ->willReturn(10)
        ;

        $queue->method('getStatisticsProvider')->willReturn($provider);

        $action = (new InfoAction('infoAction', $controller, [
            'queue' => $queue,
        ]));
        $action->run();
    }

    public function testDelayedCount()
    {
        $controller = $this->getMockBuilder(Controller::class)
            ->setConstructorArgs(['testController', new Module('testModule')])
            ->getMock()
        ;

        $controller->expects(self::exactly(3))
            ->method('stdout')
            ->withConsecutive(
                [
                    'Jobs' . PHP_EOL,
                    Console::FG_GREEN,
                ],
                [
                    '- delayed: ',
                    Console::FG_YELLOW,
                ],
                [
                    10 . PHP_EOL
                ]
            )
        ;

        $queue = $this->getMockBuilder(Queue::class)->getMock();

        $provider = $this->getMockBuilder(DelayedCountProvider::class)
            ->setConstructorArgs([$queue])
            ->getMock()
        ;
        $provider->expects(self::once())
            ->method('getDelayedCount')
            ->willReturn(10)
        ;

        $queue->method('getStatisticsProvider')->willReturn($provider);

        $action = (new InfoAction('infoAction', $controller, [
            'queue' => $queue,
        ]));
        $action->run();
    }

    public function testReservedCount()
    {
        $controller = $this->getMockBuilder(Controller::class)
            ->setConstructorArgs(['testController', new Module('testModule')])
            ->getMock()
        ;

        $controller->expects(self::exactly(3))
            ->method('stdout')
            ->withConsecutive(
                [
                    'Jobs' . PHP_EOL,
                    Console::FG_GREEN,
                ],
                [
                    '- reserved: ',
                    Console::FG_YELLOW,
                ],
                [
                    10 . PHP_EOL
                ]
            )
        ;

        $queue = $this->getMockBuilder(Queue::class)->getMock();

        $provider = $this->getMockBuilder(ReservedCountProvider::class)
            ->setConstructorArgs([$queue])
            ->getMock()
        ;
        $provider->expects(self::once())
            ->method('getReservedCount')
            ->willReturn(10)
        ;

        $queue->method('getStatisticsProvider')->willReturn($provider);

        $action = (new InfoAction('infoAction', $controller, [
            'queue' => $queue,
        ]));
        $action->run();
    }

    public function testDoneCount()
    {
        $controller = $this->getMockBuilder(Controller::class)
            ->setConstructorArgs(['testController', new Module('testModule')])
            ->getMock()
        ;

        $controller->expects(self::exactly(3))
            ->method('stdout')
            ->withConsecutive(
                [
                    'Jobs' . PHP_EOL,
                    Console::FG_GREEN,
                ],
                [
                    '- done: ',
                    Console::FG_YELLOW,
                ],
                [
                    10 . PHP_EOL
                ]
            )
        ;

        $queue = $this->getMockBuilder(Queue::class)->getMock();

        $provider = $this->getMockBuilder(DoneCountProvider::class)
            ->setConstructorArgs([$queue])
            ->getMock()
        ;
        $provider->expects(self::once())
            ->method('getDoneCount')
            ->willReturn(10)
        ;

        $queue->method('getStatisticsProvider')->willReturn($provider);

        $action = (new InfoAction('infoAction', $controller, [
            'queue' => $queue,
        ]));
        $action->run();
    }
}
