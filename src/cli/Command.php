<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\cli;

use yii\base\Exception;
use yii\console\Controller;

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
                'class' => Verbose::class,
                'command' => $this,
            ]);
        }

        if ($this->useIsolateOption($action->id) && $this->isolate) {
            $this->queue->messageHandler = function ($id, $message) {
                return $this->handleMessage($id, $message);
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
     * @return int exit code
     */
    public function actionExec($id = null)
    {
        if ($this->queue->execute($id, file_get_contents('php://stdin'))) {
            return self::EXIT_CODE_NORMAL;
        } else {
            return self::EXIT_CODE_ERROR;
        }
    }

    /**
     * Handles message using child process.
     *
     * @param string|null $id of a message
     * @param string $message
     * @return bool
     * @throws
     * @see actionExec()
     */
    private function handleMessage($id, $message)
    {
        // Executes child process
        $cmd = strtr('{php} {yii} {queue}/exec {id}', [
            '{php}' => PHP_BINARY,
            '{yii}' => $_SERVER['SCRIPT_FILENAME'],
            '{queue}' => $this->id,
            '{id}' => $id,
        ]);
        foreach ($this->getPassedOptions() as $name) {
            if (in_array($name, $this->options('exec'))) {
                $cmd .= ' --' . $name . '=' . $this->$name;
            }
        }
        if (!in_array('color', $this->getPassedOptions())) {
            $cmd .= ' --color=' . $this->isColorEnabled();
        }

        $descriptors = [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']];
        $process = proc_open($cmd, $descriptors, $pipes);
        if (is_resource($process)) {
            // Writes message to stdIn of process
            fwrite($pipes[0], $message);
            fclose($pipes[0]);
            // Reads stdOut
            $this->stdout(stream_get_contents($pipes[1]));
            fclose($pipes[1]);
            // Reads stdErr
            $this->stderr(stream_get_contents($pipes[2]));
            fclose($pipes[2]);
            // Closes process
            $exitCode = proc_close($process);

            return $exitCode == self::EXIT_CODE_NORMAL;
        } else {
            throw new Exception("Cannot execute command: $cmd");
        }
    }
}