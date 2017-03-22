<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\serializers;

use Yii;
use yii\base\Object;
use yii\helpers\Json;

/**
 * Class Json
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class JsonSerializer extends Object implements Serializer
{
    /**
     * @var string
     */
    public $classKey = 'class';
    /**
     * @var int
     */
    public $options = 0;

    /**
     * @inheritdoc
     */
    public function serialize($job)
    {
        return Json::encode($this->toArray($job), $this->options);
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        return $this->fromArray(Json::decode($serialized));
    }

    /**
     * @param mixed $data
     * @return array|mixed
     */
    protected function toArray($data)
    {
        if (is_object($data)) {
            $result = [$this->classKey => get_class($data)];
            foreach (get_object_vars($data) as $property => $value) {
                $result[$property] = $this->toArray($value);
            }

            return $result;
        }
        
        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                $result[$key] = $this->toArray($value);
            }

            return $result;
        }
        
        return $data;
    }

    /**
     * @param array $data
     * @return mixed
     */
    protected function fromArray($data)
    {
        if (!is_array($data)) {
            return $data;
        }
        
        if (!isset($data[$this->classKey])) {
            $result = [];
            foreach ($data as $key => $value) {
                $result[$key] = $this->fromArray($value);
            }
            
            return $result;
        }
        
        $config = ['class' => $data[$this->classKey]];
        unset($data[$this->classKey]);
        foreach ($data as $property => $value) {
            $config[$property] = $this->fromArray($value);
        }
        
        return Yii::createObject($config);
    }
}
