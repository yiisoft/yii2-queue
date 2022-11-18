<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue\closure;

use function Opis\Closure\serialize as opis_serialize;
use yii\queue\PushEvent;
use yii\queue\Queue;

/**
 * Closure Behavior.
 *
 * If you use the behavior, you can push closures into queue. For example:
 *
 * ```php
 * $url = 'http://example.com/name.jpg';
 * $file = '/tmp/name.jpg';
 * Yii::$app->push(function () use ($url, $file) {
 *     file_put_contents($file, file_get_contents($url));
 * });
 * ```
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Behavior extends \yii\base\Behavior
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
            Queue::EVENT_BEFORE_PUSH => 'beforePush',
        ];
    }

    /**
     * Converts the closure to a job object.
     * @param PushEvent $event
     */
    public function beforePush(PushEvent $event)
    {
        $serialized = opis_serialize($event->job);
        $event->job = new Job();
        $event->job->serialized = $serialized;
    }
}
