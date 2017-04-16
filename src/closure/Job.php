<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\closure;

use SuperClosure\Serializer;
use yii\base\Object;
use zhuravljov\yii\queue\Job as BaseJob;

/**
 * Closure Job
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Job extends Object implements BaseJob
{
    /**
     * @var string serialized closure
     */
    public $serialized;

    /**
     * Unserializes and executes a closure
     * @inheritdoc
     */
    public function execute($queue)
    {
        $serializer = new Serializer();
        $closure = $serializer->unserialize($this->serialized);
        $closure();
    }
}