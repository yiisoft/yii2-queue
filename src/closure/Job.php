<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\closure;

use SuperClosure\Serializer;
use yii\base\Object;
use yii\queue\Job as BaseJob;

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