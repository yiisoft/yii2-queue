<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\closure;

use SuperClosure\Serializer;
use zhuravljov\yii\queue\PushEvent;
use zhuravljov\yii\queue\Queue;

/**
 * Closure Behavior
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
     * Converts the closure to a job object
     * @param PushEvent $event
     */
    public function beforePush(PushEvent $event)
    {
        if ($event->job instanceof \Closure) {
            $serializer = new Serializer();
            $serialized = $serializer->serialize($event->job);
            $event->job = new Job(['serialized' => $serialized]);
        }
    }
}
