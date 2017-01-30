<?php

namespace tests;

/**
 * Process runner
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Process
{
    /**
     * Runs command.
     *
     * @param string $cmd
     */
    public static function run($cmd)
    {
        exec($cmd);
    }

    /**
     * Start demon process.
     *
     * @param string $cmd
     * @return integer process pid
     */
    public static function start($cmd)
    {
        return (int) exec(strtr('nohup {cmd} >/dev/null 2>&1 & echo $!', ['{cmd}' => $cmd]));
    }

    /**
     * Stop demon process.
     *
     * @param integer $pid
     */
    public static function stop($pid)
    {
        exec("kill $pid");
    }
}