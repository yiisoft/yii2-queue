<?php

declare(strict_types=1);

use yii\console\controllers\MigrateController;
use tests\app\benchmark\Controller;

return [
    'controllerMap' => [
        'mysql-migrate' => [
            'class' => MigrateController::class,
            'db' => 'mysql',
            'migrationPath' => null,
            'migrationNamespaces' => [
                'yii\queue\db\migrations',
            ],
        ],
        'sqlite-migrate' => [
            'class' => MigrateController::class,
            'db' => 'sqlite',
            'migrationPath' => null,
            'migrationNamespaces' => [
                'yii\queue\db\migrations',
            ],
        ],
        'pgsql-migrate' => [
            'class' => MigrateController::class,
            'db' => 'pgsql',
            'migrationPath' => null,
            'migrationNamespaces' => [
                'yii\queue\db\migrations',
            ],
        ],
        'benchmark' => Controller::class,
    ],
];
