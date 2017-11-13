<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\cli;

use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Class Command
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class Command extends Controller
{
    /**
     * @var Queue
     */
    public $queue;
    /**
     * @var bool verbose mode of a job execute. If enabled, execute result of each job
     * will be printed.
     */
    public $verbose = false;
    /**
     * @var bool isolate mode. It executes a job in a child process.
     */
    public $isolate = true;


    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        $options = parent::options($actionID);
        if ($this->useVerboseOption($actionID)) {
            $options[] = 'verbose';
        }
        if ($this->useIsolateOption($actionID)) {
            $options[] = 'isolate';
        }

        return $options;
    }

    /**
     * @inheritdoc
     */
    public function optionAliases()
    {
        return array_merge(parent::optionAliases(), [
            'v' => 'verbose',
        ]);
    }

    /**
     * @param string $actionID
     * @return bool
     */
    protected function useVerboseOption($actionID)
    {
        return in_array($actionID, ['exec', 'run', 'listen']);
    }

    /**
     * @param string $actionID
     * @return bool
     */
    protected function useIsolateOption($actionID)
    {
        return in_array($actionID, ['run', 'listen']);
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if ($this->useVerboseOption($action->id) && $this->verbose) {
            $this->queue->attachBehavior('verbose', [
                'class' => VerboseBehavior::class,
                'command' => $this,
            ]);
        }

        if ($this->useIsolateOption($action->id) && $this->isolate) {
            $this->queue->messageHandler = function ($id, $message, $ttr, $attempt) {
                return $this->handleMessage($id, $message, $ttr, $attempt);
            };
        } else {
            $this->queue->messageHandler = null;
        }

        return parent::beforeAction($action);
    }

    /**
     * Executes a job.
     *
     * @param string|null $id of a message
     * @param int $ttr time to reserve
     * @param int $attempt number
     * @return int exit code
     */
    public function actionExec($id, $ttr, $attempt)
    {
        if ($this->queue->execute($id, file_get_contents('php://stdin'), $ttr, $attempt)) {
            return ExitCode::OK;
        } else {
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Handles message using child process.
     *
     * @param string|null $id of a message
     * @param string $message
     * @param int $ttr time to reserve
     * @param int $attempt number
     * @return bool
     * @throws
     * @see actionExec()
     */
    protected function handleMessage($id, $message, $ttr, $attempt)
    {
        // Executes child process        
        $cmd = strtr('{php} {yii} {queue}/exec "{id}" "{ttr}" "{attempt}"', [
            '{php}' => PHP_BINARY,
            '{yii}' => $_SERVER['SCRIPT_FILENAME'],
            '{queue}' => $this->uniqueId,
            '{id}' => $id,
            '{ttr}' => $ttr,
            '{attempt}' => $attempt,
        ]);
        foreach ($this->getPassedOptions() as $name) {
            if (in_array($name, $this->options('exec'))) {
                $cmd .= ' --' . $name . '=' . $this->$name;
            }
        }
        if (!in_array('color', $this->getPassedOptions())) {
            $cmd .= ' --color=' . $this->isColorEnabled();
        }

        $process = new Process($cmd, null, null, $message, $ttr);
        try {
            $exitCode = $process->run(function ($type, $buffer) {
                if ($type === Process::ERR) {
                    $this->stderr($buffer);
                } else {
                    $this->stdout($buffer);
                }
            });
        } catch (ProcessTimedOutException $error) {
            $job = $this->queue->serializer->unserialize($message);
            return $this->queue->handleError($id, $job, $ttr, $attempt, $error);
        }

        return $exitCode == ExitCode::OK;
    }
}