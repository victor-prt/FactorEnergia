<?php

/** @var yii\web\View $this */
/** @var string $content */

use yii\helpers\Html;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= Html::encode($this->title ?: 'FactorEnergia') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: system-ui, sans-serif; max-width: 720px; margin: 2rem auto; padding: 0 1rem; line-height: 1.5; }
        code { background: #f4f4f4; padding: 0.15rem 0.35rem; border-radius: 4px; }
    </style>
</head>
<body>
<?= $content ?>
</body>
</html>
