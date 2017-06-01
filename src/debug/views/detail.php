<?php
/**
 * @var \yii\web\View $this
 * @var array $jobs
 */

use yii\helpers\Html;

$styles = [
    'unknown' => 'default',
    'waiting' => 'info',
    'reserved' => 'warning',
    'done' => 'success',
];
?>
<h1>Pushed <?= count($jobs) ?> jobs</h1>

<?php foreach ($jobs as $job): ?>
    <div class="panel panel-<?= isset($styles[$job['status']]) ? $styles[$job['status']] : 'danger' ?>">
        <div class="panel-heading">
            <h3 class="panel-title">
                <?php if (is_string($job['id'])): ?>
                    <?= Html::encode($job['id']) ?> -
                <?php endif; ?>
                <?= isset($job['class']) ? Html::encode($job['class']) : 'Mixed data' ?>
            </h3>
        </div>
        <table class="table">
            <tr>
                <th>Sender</th>
                <td><?= Html::encode($job['sender']) ?></td>
            </tr>
            <?php if (isset($job['id'])): ?>
                <tr>
                    <th>ID</th>
                    <td><?= Html::encode($job['id']) ?></td>
                </tr>
            <?php endif; ?>
            <tr>
                <th>TTR</th>
                <td><?= Html::encode($job['ttr']) ?></td>
            </tr>
            <?php if ($job['delay']): ?>
                <tr>
                    <th>Delay</th>
                    <td><?= Html::encode($job['delay']) ?></td>
                </tr>
            <?php endif; ?>
            <?php if (isset($job['priority'])): ?>
                <tr>
                    <th>Priority</th>
                    <td><?= Html::encode($job['priority']) ?></td>
                </tr>
            <?php endif; ?>
            <tr>
                <th>Status</th>
                <td><?= Html::encode($job['status']) ?></td>
            </tr>
            <?php if (isset($job['class'])): ?>
                <tr>
                    <th>Class</th>
                    <td><?= Html::encode($job['class']) ?></td>
                </tr>
                <?php foreach ($job['properties'] as $property => $value): ?>
                    <tr>
                        <th><?= Html::encode($property) ?></th>
                        <td><?= Html::encode($value) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <th>Data</th>
                    <td><?= Html::encode($job['data']) ?></td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
<?php endforeach; ?>
<?php
$this->registerCss(<<<CSS

.panel > .table th {
    width: 25%;
}

CSS
);