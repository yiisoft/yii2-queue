<?php

namespace zhuravljov\yii\queue;

use yii\console\Controller;

/**
 * Class Command
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class Command extends Controller
{
    /**
     * @var Driver
     */
    public $driver;
    /**
     * @var boolean
     */
    public $verbose = false;

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        return array_merge(parent::options($actionID), [
            'verbose',
        ]);
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
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if ($this->verbose) {
            $this->driver->queue->attachBehavior('verbose', VerboseBehavior::class);
        }

        return parent::beforeAction($action);
    }
}