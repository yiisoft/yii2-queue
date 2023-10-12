<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue;

use Exception;
use Throwable;

/**
 * Invalid Job Exception.
 *
 * Throws when serialized message cannot be unserialized to a job.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 * @since 2.1.1
 */
class InvalidJobException extends Exception
{
    /**
     * @param string $serialized
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        private string $serialized,
        string $message = '',
        int $code = 0,
        Throwable $previous = null
    )
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string of serialized message that cannot be unserialized to a job
     */
    final public function getSerialized(): string
    {
        return $this->serialized;
    }
}
