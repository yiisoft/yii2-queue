<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace tests\drivers\amqp_interop;

use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\Process;
use tests\TestCase;
use yii\base\Module;
use yii\queue\amqp_interop\Command;
use yii\queue\amqp_interop\Queue;

/**
 * AMQP Interop command signal handling.
 */
final class CommandTest extends TestCase
{
    /**
     * @var list<int>
     */
    private const array SIGNALS = [SIGHUP, SIGINT, SIGQUIT, SIGTERM];

    /**
     * @var array<int, callable|int>
     */
    private array $originalHandlers = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::SIGNALS as $signal) {
            $this->originalHandlers[$signal] = pcntl_signal_get_handler($signal);
            pcntl_signal($signal, SIG_DFL);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->originalHandlers as $signal => $handler) {
            pcntl_signal($signal, $handler);
        }

        parent::tearDown();
    }

    public function testBootstrapKeepsExistingSignalHandlers(): void
    {
        $bootstrap = var_export(dirname(__DIR__, 2) . '/bootstrap.php', true);
        $process = $this->runCode(<<<PHP
            pcntl_signal(SIGINT, static function (): void {
                echo 'kept';
            });
            require {$bootstrap};
            posix_kill(posix_getpid(), SIGINT);
            pcntl_signal_dispatch();
            echo 'alive';
            PHP, false);

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $this->assertFalse($process->hasBeenSignaled(), $process->getErrorOutput());
        $this->assertSame('keptalive', $process->getOutput(), $process->getErrorOutput());
    }

    public function testBootstrapDoesNotRegisterSignalHandlers(): void
    {
        $process = $this->runCode(<<<'PHP'
            foreach ([SIGHUP, SIGINT, SIGQUIT, SIGTERM] as $signal) {
                if (pcntl_signal_get_handler($signal) !== SIG_DFL) {
                    fwrite(STDERR, "signal {$signal} handler was replaced\n");
                    exit(1);
                }
            }

            echo 'ok';
            PHP);

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $this->assertSame('ok', $process->getOutput(), $process->getErrorOutput());
    }

    public function testQueueInitDoesNotRegisterSignalHandlers(): void
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

    public function testCommandCallsPreviousHandlerAndTerminates(): void
    {
        $process = $this->runCode(<<<'PHP'
            pcntl_signal(SIGINT, static function (int $signal): void {
                echo 'previous';
            });
            new \yii\queue\amqp_interop\Command('queue', Yii::$app, [
                'queue' => new \yii\queue\amqp_interop\Queue(),
            ]);
            posix_kill(posix_getpid(), SIGINT);
            pcntl_signal_dispatch();
            echo 'alive';
            PHP);

        $this->assertSame('previous', $process->getOutput(), $process->getErrorOutput());
        $this->assertTrue($process->hasBeenSignaled(), $process->getErrorOutput());
        $this->assertSame(SIGINT, $process->getTermSignal());
    }

    public function testCommandTerminatesProcessBySignal(): void
    {
        foreach (self::SIGNALS as $signal) {
            $process = $this->runCode(<<<PHP
                new \\yii\\queue\\amqp_interop\\Command('queue', Yii::\$app, [
                    'queue' => new \\yii\\queue\\amqp_interop\\Queue(),
                ]);
                posix_kill(posix_getpid(), {$signal});
                pcntl_signal_dispatch();
                echo 'alive';
                PHP);

            $this->assertSame('', $process->getOutput(), $process->getErrorOutput());
            $this->assertTrue($process->hasBeenSignaled(), $process->getErrorOutput());
            $this->assertSame($signal, $process->getTermSignal());
        }
    }

    /**
     * Runs the code in a separate process.
     * The test application is bootstrapped before the code unless $bootstrap is false.
     */
    private function runCode(string $code, bool $bootstrap = true): Process
    {
        if ($bootstrap) {
            $code = 'require ' . var_export(dirname(__DIR__, 2) . '/bootstrap.php', true) . ';' . $code;
        }

        $process = new Process([
            PHP_BINARY,
            '-d',
            'error_reporting=' . (E_ALL & ~E_DEPRECATED),
            '-r',
            $code,
        ]);
        $process->setTimeout(30);

        try {
            $process->run();
        } catch (ProcessSignaledException) {
            // The handler restores the default action and re-raises the signal.
        }

        return $process;
    }
}
