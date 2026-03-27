<?php

namespace app\controllers;

use app\models\Contract;
use app\services\ErseSyncService;
use Yii;
use yii\filters\ContentNegotiator;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;

class ApiController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'sync' => ['POST'],
                ],
            ],
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'only' => ['sync'],
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
        ];
    }

    /**
     * POST /api/contracts/sync — Parte 3.3
     */
    public function actionSync(): array
    {
        $body = Yii::$app->request->getBodyParams();
        if (!isset($body['contract_id'])) {
            throw new BadRequestHttpException('Falta contract_id en el cuerpo JSON.');
        }
        $contractId = $body['contract_id'];
        if (!is_numeric($contractId) || (int) $contractId <= 0) {
            throw new BadRequestHttpException('contract_id debe ser un entero positivo.');
        }
        $contractId = (int) $contractId;

        $contract = Contract::find()
            ->joinWith('client')
            ->where(['contracts.id' => $contractId])
            ->one();

        if ($contract === null) {
            Yii::$app->response->statusCode = 404;
            return [
                'error' => 'not_found',
                'message' => 'Contrato no encontrado.',
            ];
        }

        if ($contract->client === null || $contract->client->country !== 'PT') {
            Yii::$app->response->statusCode = 422;
            return [
                'error' => 'validation_error',
                'message' => 'Solo se pueden sincronizar contratos cuyo cliente tiene país PT.',
            ];
        }

        /** @var ErseSyncService $svc */
        $svc = Yii::$app->get('erseSync');
        $result = $svc->syncContract($contract);
        Yii::$app->response->statusCode = $result['httpStatus'];

        return $result['body'];
    }
}
