<?php

return [
    'class' => yii\db\Connection::class,
    'dsn' => getenv('DB_DSN') ?: 'sqlsrv:Server=localhost,1433;Database=factor;Encrypt=yes;TrustServerCertificate=1',
    'username' => getenv('DB_USER') ?: 'sa',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset' => 'UTF-8',
    'enableSchemaCache' => false,
];
