<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue;

use Yii;
use yii\base\Behavior;

/**
 * Class LogBehavior
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class LogBehavior extends Behavior
{
    /**
     * @var Queue
     */
    public $owner;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            Queue::EVENT_AFTER_PUSH => function (JobEvent $event) {
                if ($event->job instanceof Job) {
                    Yii::info(get_class($event->job) . ' pushed.', Queue::class);
                } else {
                    Yii::info('Mixed data pushed.', Queue::class);
                }
            },
            Queue::EVENT_BEFORE_WORK => function (JobEvent $event) {
                Yii::info(get_class($event->job) . ' started.', Queue::class);
            },
            Queue::EVENT_AFTER_WORK => function (JobEvent $event) {
                Yii::info(get_class($event->job) . ' finished.', Queue::class);
                Yii::getLogger()->flush(true);
            },
            Queue::EVENT_AFTER_ERROR => function (ErrorEvent $event) {
                Yii::error(get_class($event->job) . ' error ' . $event->error, Queue::class);
                Yii::getLogger()->flush(true);
            },
        ];
    }
}