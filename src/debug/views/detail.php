<?php
/**
 * @var \yii\web\View $this
 * @var Job[]|mixed[] $jobs
 */

use yii\helpers\Html;
use yii\helpers\VarDumper;
use zhuravljov\yii\queue\Job;

?>
<h1>Pushed <?= count($jobs) ?> jobs</h1>

<?php foreach ($jobs as $job): ?>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">
                <?= $job instanceof Job ? Html::encode(get_class($job)) : 'Mixed data' ?></h3>
        </div>
        <div class="panel-body">
            <?= VarDumper::dumpAsString($job, 10, true) ?>
        </div>
    </div>
<?php endforeach; ?>
