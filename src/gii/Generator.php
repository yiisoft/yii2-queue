<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\gii;

use Yii;
use yii\base\Object;
use yii\gii\CodeFile;
use zhuravljov\yii\queue\Job;

/**
 * This generator will generate a job.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Generator extends \yii\gii\Generator
{
    public $jobClass;
    public $properties;
    public $ns = 'app\jobs';
    public $baseClass = __NAMESPACE__ . "\\" . get_class(new Object());

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'Job Generator';
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return 'This generator generates a Job class for the queue.';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['jobClass', 'properties', 'ns', 'baseClass'], 'trim'],
            [['jobClass', 'ns', 'baseClass'], 'required'],
            ['jobClass', 'match', 'pattern' => '/^\w+$/', 'message' => 'Only word characters are allowed.'],
            ['jobClass', 'validateJobClass'],
            ['properties', 'match', 'pattern' => '/^[a-z_][a-z0-9_,\\s]*$/i', 'message' => 'Must be valid class properties.'],
            ['ns', 'validateNamespace'],
            ['baseClass', 'validateClass'],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'jobClass' => 'Job Class',
            'properties' => 'Job Properties',
            'ns' => 'Namespace',
            'baseClass' => 'Base Class',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function hints()
    {
        return array_merge(parent::hints(), [
            'jobClass' => 'This is the name of the Job class to be generated, e.g., <code>SomeJob</code>.',
            'properties' => 'Job object property names. Separate multiple properties with commas or spaces, e.g., <code>prop1, prop2</code>.',
            'ns' => 'This is the namespace of the Job class to be generated.',
            'baseClass' => 'This is the class that the new Job class will extend from.',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function stickyAttributes()
    {
        return array_merge(parent::stickyAttributes(), ['ns', 'baseClass']);
    }

    /**
     * @inheritdoc
     */
    public function requiredTemplates()
    {
        return ['job.php'];
    }

    /**
     * @inheritdoc
     */
    public function generate()
    {
        $params = [];
        $params['jobClass'] = $this->jobClass;
        $params['ns'] = $this->ns;
        $params['baseClass'] = '\\' . ltrim($this->baseClass, '\\');
        $params['interfaces'] = [];
        $class = __NAMESPACE__ . "\\" . get_class(new Job());
        if (!is_a($this->baseClass, __NAMESPACE__ . "\\" . get_class($class), true)) {
            $params['interfaces'][] = '\\' . $class;
        }
        $params['properties'] = array_unique(preg_split('/[\s,]+/', $this->properties, -1, PREG_SPLIT_NO_EMPTY));

        $jobFile = new CodeFile(
            Yii::getAlias('@' . str_replace('\\', '/', $this->ns)) . '/' . $this->jobClass . '.php',
            $this->render('job.php', $params)
        );

        return [$jobFile];
    }

    public function validateJobClass($attribute)
    {
        if ($this->isReservedKeyword($this->$attribute)) {
            $this->addError($attribute, 'Class name cannot be a reserved PHP keyword.');
        }
    }

    /**
     * Validates the namespace.
     *
     * @param string $attribute Namespace variable.
     */
    public function validateNamespace($attribute)
    {
        $value = $this->$attribute;
        $value = ltrim($value, '\\');
        $path = Yii::getAlias('@' . str_replace('\\', '/', $value), false);
        if ($path === false) {
            $this->addError($attribute, 'Namespace must be associated with an existing directory.');
        }
    }
}