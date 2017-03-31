<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue;

use yii\base\Behavior;
use yii\helpers\Console;

/**
 * Class VerboseBehavior
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class VerboseBehavior extends Behavior
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
            Queue::EVENT_BEFORE_WORK => function (JobEvent $event) {
                Console::stdout(strtr('{time}: {class} has been started ... ', [
                    '{time}' => date('Y-m-d H:i:s'),
                    '{class}' => get_class($event->job),
                ]));
            },
            Queue::EVENT_AFTER_WORK => function (JobEvent $event) {
                Console::output('OK');
            },
            Queue::EVENT_AFTER_ERROR => function (ErrorEvent $event) {
                Console::output('Error');
                Console::error($event->error);
            },
        ];
    }

}