<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue;

use Throwable;

/**
 * Invalid Job Exception.
 *
 * Throws when serialized message cannot be unserialized to a job.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 * @since 2.1.1
 */
class InvalidJobException extends \Exception
{
    /**
     * @var string
     */
    private $serialized;


    /**
     * @param string $serialized
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($serialized, $message = '', $code = 0, Throwable $previous = null)
    {
        $this->serialized = $serialized;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string of serialized message that cannot be unserialized to a job
     */
    final public function getSerialized()
    {
        return $this->serialized;
    }
}
