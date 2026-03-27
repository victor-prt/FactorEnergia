<?php

/** @var yii\web\View $this */

use yii\helpers\Html;
use Yii;

$this->title = 'Prueba técnica FactorEnergia';

?>
<h1>FactorEnergia — entrega de prueba</h1>
<p>Framework: <strong>Yii2</strong>. Idioma de documentación: <strong>español</strong>.</p>
<p>Endpoint de sincronización ERSE:</p>
<pre><code>POST <?= Html::encode(Yii::$app->request->hostInfo) ?>/api/contracts/sync
Content-Type: application/json

{"contract_id": 1}</code></pre>
<p>Si no aplican URLs amigables: <code>POST .../index.php?r=api/sync</code></p>
<p>Consulta el <code>README.md</code> en la raíz del repositorio para Docker y Composer.</p>
