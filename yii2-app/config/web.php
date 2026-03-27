<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'factor-energia-web',
    'name' => 'FactorEnergia — Prueba técnica',
    'basePath' => dirname(__DIR__),
    'defaultRoute' => 'site/index',
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            'class' => yii\web\Request::class,
            'cookieValidationKey' => getenv('COOKIE_VALIDATION_KEY') ?: 'CHANGE_ME_IN_ENV',
            'parsers' => [
                'application/json' => yii\web\JsonParser::class,
            ],
        ],
        'response' => [
            'class' => yii\web\Response::class,
        ],
        'cache' => [
            'class' => yii\caching\FileCache::class,
        ],
        'user' => [
            'identityClass' => app\models\User::class,
            'enableAutoLogin' => false,
            'enableSession' => false,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'POST api/contracts/sync' => 'api/sync',
                '' => 'site/index',
            ],
        ],
        'spotPriceClient' => [
            'class' => app\components\FileGetContentsSpotPriceClient::class,
        ],
        'erseHttpSender' => [
            'class' => app\components\erse\ErseHttpSender::class,
            'baseUrl' => $params['erse']['baseUrl'] ?? 'https://api.erse.pt/v2',
            'bearerToken' => $params['erse']['bearerToken'] ?? '',
        ],
        'erseMockSender' => [
            'class' => app\components\erse\ErseMockSender::class,
        ],
        'erseSenderFactory' => [
            'class' => app\components\erse\ErseSenderFactory::class,
            'mock' => (bool) ($params['erse']['mock'] ?? false),
            'httpSender' => 'erseHttpSender',
            'mockSender' => 'erseMockSender',
        ],
        'invoiceCalculator' => [
            'class' => app\services\InvoiceCalculatorService::class,
            'db' => 'db',
            'spotPriceClient' => 'spotPriceClient',
        ],
        'erseSync' => [
            'class' => app\services\ErseSyncService::class,
            'db' => 'db',
            'senderFactory' => 'erseSenderFactory',
        ],
    ],
    'params' => $params,
];

if (YII_DEBUG && class_exists(yii\debug\Module::class)) {
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => yii\debug\Module::class,
        'allowedIPs' => ['*'],
    ];
}

return $config;
