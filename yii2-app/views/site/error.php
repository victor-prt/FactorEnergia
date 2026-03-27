<?php

/** @var yii\web\View $this */
/** @var Exception $exception */

use yii\helpers\Html;

$this->title = 'Error';
$code = ($exception instanceof \yii\web\HttpException) ? $exception->statusCode : 500;
?>
<h1>Error <?= Html::encode((string) $code) ?></h1>
<p><?= nl2br(Html::encode($exception->getMessage())) ?></p>
