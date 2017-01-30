<?php
return [
    'controllerMap' => [
        'migrate' => [
            'class' => \yii\console\controllers\MigrateController::class,
            'migrationPath' => null,
            'migrationNamespaces' => [
                'zhuravljov\yii\queue\db\migrations',
            ],
        ],
    ],
];