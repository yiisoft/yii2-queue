<?php
/**
 * @var \yii\web\View $this
 * @var \yii\widgets\ActiveForm $form
 * @var \yii\queue\gii\Generator $generator
 */

echo $form->field($generator, 'jobClass')->textInput(['autofocus' => true]);
echo $form->field($generator, 'properties');
echo $form->field($generator, 'retryable')->checkbox();
echo $form->field($generator, 'ns');
echo $form->field($generator, 'baseClass');
