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
    ],
];