#!/usr/bin/env php
<?php
/**
 * Runs a command with network sync using MySQL.
 *
 * When network of docker containers is starting, each php container executes
 * DB migration command. To except chance to execute many commands at the same time,
 * network sync is used. It uses `GET_LOCK()` and `RELEASE_LOCK()` MySQL functions.
 * This ensures monopoly execution. One of the commands will be run, and others will wait.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */

$params = $_SERVER['argv'];
array_shift($params);
$command = implode(' ', $params);
$lockName = md5($command);

$mysql = new PDO(
    sprintf('mysql:host=%s;dbname=%s', getenv('MYSQL_HOST'), getenv('MYSQL_DATABASE')),
    getenv('MYSQL_USER'),
    getenv('MYSQL_PASSWORD')
);

// Waiting a lock for the command
$query = $mysql->prepare('SELECT GET_LOCK(?, -1)');
$query->execute([$lockName]);
if (!$query->fetch(PDO::FETCH_NUM)[0]) {
    echo basename(__FILE__) . ': cannot get the lock.' . PHP_EOL;
    exit(1);
}

// Executes the command
passthru($command, $exitCode);

// Releases the lock
$query = $mysql->prepare('SELECT RELEASE_LOCK(?)');
$query->execute([$lockName]);
if (!$query->fetch(PDO::FETCH_NUM)[0]) {
    echo basename(__FILE__) . ': cannot release the lock.' . PHP_EOL;
    exit(1);
}

exit($exitCode);