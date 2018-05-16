<?php
return [
    'controllerMap' => [
        'mysql-migrate' => [
            'class' => \yii\console\controllers\MigrateController::class,
            'db' => 'mysql',
            'migrationPath' => null,
            'migrationNamespaces' => [
                'yii\queue\db\migrations',
            ],
        ],
        'sqlite-migrate' => [
            'class' => \yii\console\controllers\MigrateController::class,
            'db' => 'sqlite',
            'migrationPath' => null,
            'migrationNamespaces' => [
                'yii\queue\db\migrations',
            ],
        ],
        'pgsql-migrate' => [
            'class' => \yii\console\controllers\MigrateController::class,
            'db' => 'pgsql',
            'migrationPath' => null,
            'migrationNamespaces' => [
                'yii\queue\db\migrations',
            ],
        ],
        'benchmark' => \tests\app\benchmark\Controller::class,
    ],
];
