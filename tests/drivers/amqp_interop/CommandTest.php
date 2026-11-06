<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace tests\drivers\amqp_interop;

use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\Process;
use tests\TestCase;
use yii\base\Module;
use yii\queue\amqp_interop\Command;
use yii\queue\amqp_interop\Queue;

/**
 * AMQP Command Test.
 */
#[RequiresPhpExtension('pcntl')]
#[RequiresPhpExtension('posix')]
final class CommandTest extends TestCase
{
    private const SIGNALS = [SIGHUP, SIGINT, SIGQUIT, SIGTERM];

    protected function setUp(): void
    {
        parent::setUp();

        $this->resetSignalHandlers();
    }

    protected function tearDown(): void
    {
        $this->resetSignalHandlers();

        parent::tearDown();
    }

    public function testQueueDoesNotRegisterSignalHandlers(): void
    {
        new Queue();

        foreach (self::SIGNALS as $signal) {
            $this->assertSame(SIG_DFL, pcntl_signal_get_handler($signal));
        }
    }

    public function testCommandRegistersSignalHandlers(): void
    {
        new Command('queue', new Module('test'), ['queue' => new Queue()]);

        foreach (self::SIGNALS as $signal) {
            $this->assertIsCallable(pcntl_signal_get_handler($signal));
        }
    }

    public function testCommandTerminatesProcessBySignal(): void
    {
        foreach (self::SIGNALS as $signal) {
            $process = $this->runCode("
                new \\yii\\queue\\amqp_interop\\Command('queue', Yii::\$app, ['queue' => Yii::\$app->amqpInteropQueue]);
                posix_kill(posix_getpid(), $signal);
                pcntl_signal_dispatch();
                echo 'alive';
            ");

            $this->assertSame('', $process->getOutput());
            $this->assertTrue($process->hasBeenSignaled());
            $this->assertSame($signal, $process->getTermSignal());
        }
    }

    public function testCommandCallsPreviousSignalHandler(): void
    {
        $process = $this->runCode("
            pcntl_signal(SIGINT, static function () {
                echo 'previous';
            });
            new \\yii\\queue\\amqp_interop\\Command('queue', Yii::\$app, ['queue' => Yii::\$app->amqpInteropQueue]);
            posix_kill(posix_getpid(), SIGINT);
            pcntl_signal_dispatch();
        ");

        $this->assertSame('previous', $process->getOutput());
        $this->assertTrue($process->hasBeenSignaled());
        $this->assertSame(SIGINT, $process->getTermSignal());
    }

    /**
     * Runs the code in a separate process with the test application.
     */
    private function runCode(string $code): Process
    {
        $process = new Process([
            PHP_BINARY,
            '-d',
            'error_reporting=' . (E_ALL & ~E_DEPRECATED),
            '-r',
            'require ' . var_export(dirname(__DIR__, 2) . '/bootstrap.php', true) . ';' . $code,
        ]);
        try {
            $process->run();
        } catch (ProcessSignaledException) {
            // Termination by signal is checked by the test
        }

        return $process;
    }

    private function resetSignalHandlers(): void
    {
        foreach (self::SIGNALS as $signal) {
            pcntl_signal($signal, SIG_DFL);
        }
    }
}
