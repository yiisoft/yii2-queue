<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue;

/**
 * Job Interface.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
interface DefaultJobInterface extends JobInterface
{

    /**
     * @param Array $json json object
     * @return void
     */
    public function setJson($json);

}
