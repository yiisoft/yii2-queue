<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\serializers;

use Yii;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use yii\helpers\Json;

/**
 * Json Serializer.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class JsonSerializer extends BaseObject implements SerializerInterface
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
     * @throws InvalidConfigException
     */
    protected function toArray($data)
    {
        if (is_object($data)) {
            $result = [$this->classKey => get_class($data)];
            foreach (get_object_vars($data) as $property => $value) {
                if ($property === $this->classKey) {
                    throw new InvalidConfigException("Object cannot contain $this->classKey property.");
                }
                $result[$property] = $this->toArray($value);
            }

            return $result;
        }

        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                if ($key === $this->classKey) {
                    throw new InvalidConfigException("Array cannot contain $this->classKey key.");
                }
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
