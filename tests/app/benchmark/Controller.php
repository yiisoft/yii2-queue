<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace tests\app\benchmark;

use yii\console\Controller as ConsoleController;

/**
 * Benchmark commands.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Controller extends ConsoleController
{
    private int $startedAt;

    public function actions(): array
    {
        return [
            'waiting' => waiting\Action::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        $this->startedAt = time();
        return parent::beforeAction($action);
    }

    /**
     * @inheritdoc
     */
    public function afterAction($action, $result)
    {
        $duration = time() - $this->startedAt;
        $this->stdout("\nCompleted in {$duration} s.\n");
        return parent::afterAction($action, $result);
    }
}
