#!/usr/bin/env php
<?php
/**
 * Запуск команды с сетевой синхронизацией через MySQL.
 *
 * Когда запускается сеть из docker-контейнеров каждый php-контейнер в числе
 * прочих запускает команду миграции БД. И, чтобы исключить высокую вероятность,
 * запуска нескольких таких процессов одновременно, используется синхронизация
 * на уровне блокировок MySQL. Это гарантирует, что одновременно будет работать
 * только одна из запущенных команд, а остальные будут ждать завершения.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */

$params = $_SERVER['argv'];
array_shift($params);
$command = implode(' ', $params);

$mysql = new PDO(
    sprintf(
        'mysql:host=%s;port=%s;dbname=%s',
        getenv('MYSQL_HOST'),
        getenv('MYSQL_PORT'),
        getenv('MYSQL_DATABASE')
    ),
    getenv('MYSQL_USER'),
    getenv('MYSQL_PASSWORD')
);

// Waiting a lock for the command
$query = $mysql->prepare('SELECT GET_LOCK(?, -1)');
$query->execute([md5($command)]);
if (!$query->fetch(PDO::FETCH_NUM)[0]) {
    throw new Exception('Cannot get the lock.');
}

// Executes the command
passthru($command, $exitCode);

// Releases the lock
$query = $mysql->prepare('SELECT RELEASE_LOCK(?)');
$query->execute([md5($command)]);
if (!$query->fetch(PDO::FETCH_NUM)[0]) {
    throw new Exception('Cannot release the lock.');
}

exit($exitCode);