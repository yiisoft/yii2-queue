<?php
return [
    'controllerMap' => [
        'mysql-migrate' => [
            'class' => \yii\console\controllers\MigrateController::class,
            'db' => 'mysql',
            'migrationPath' => null,
            'migrationNamespaces' => [
                'zhuravljov\yii\queue\db\migrations',
            ],
        ],
        'sqlite-migrate' => [
            'class' => \yii\console\controllers\MigrateController::class,
            'db' => 'sqlite',
            'migrationPath' => null,
            'migrationNamespaces' => [
                'zhuravljov\yii\queue\db\migrations',
            ],
        ],
    ],
];