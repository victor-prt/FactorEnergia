<?php

namespace app\services;

use app\models\Contract;
use app\models\ErseContractSync;
use Yii;
use yii\base\Component;
use yii\db\Connection;
use yii\di\Instance;
use yii\helpers\Json;
use yii\httpclient\Client;
/**
 * Sincronización de contratos con la API ERSE (Parte 3.2).
 */
class ErseSyncService extends Component
{
    /** @var Connection|array|string */
    public $db = 'db';

    public function init(): void
    {
        parent::init();
        if ($this->db === 'db') {
            $this->db = Yii::$app->db;
        } else {
            $this->db = Instance::ensure($this->db, Connection::class);
        }
    }

    /**
     * @return array{httpStatus:int, body:array}
     */
    public function syncContract(Contract $contract): array
    {
        $contract->load(['client', 'tariff']);

        $prior = ErseContractSync::find()
            ->where([
                'contract_id' => $contract->id,
                'sync_status' => ErseContractSync::STATUS_SUCCESS,
            ])
            ->andWhere(['not', ['erse_id' => null]])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        if ($prior !== null) {
            return [
                'httpStatus' => 409,
                'body' => [
                    'error' => 'already_synced',
                    'erse_id' => $prior->erse_id,
                    'message' => 'El contrato ya fue sincronizado correctamente con ERSE.',
                ],
            ];
        }

        $sync = new ErseContractSync();
        $sync->contract_id = $contract->id;
        $sync->sync_status = ErseContractSync::STATUS_PENDING;
        $now = date('Y-m-d H:i:s');
        $sync->created_at = $now;
        $sync->updated_at = $now;
        if (!$sync->save(false)) {
            return [
                'httpStatus' => 500,
                'body' => ['error' => 'internal_error', 'message' => 'No se pudo crear el registro de sincronización.'],
            ];
        }

        $payload = $this->buildPayload($contract);

        // Modo de prueba local: evita llamadas externas a ERSE (útil cuando no hay DNS/egreso).
        if (Yii::$app->params['erse']['mock'] ?? false) {
            $sync->sync_status = ErseContractSync::STATUS_SUCCESS;
            $sync->erse_id = 'ERSE-MOCK-' . $contract->id;
            $sync->erse_response = Json::encode([
                'mock' => true,
                'erse_id' => $sync->erse_id,
                'status' => 'registered',
                'requestPayload' => $payload,
            ]);
            $sync->updated_at = date('Y-m-d H:i:s');
            $sync->save(false);

            return [
                'httpStatus' => 201,
                'body' => [
                    'erse_id' => $sync->erse_id,
                    'status' => 'registered',
                    'sync_id' => $sync->id,
                ],
            ];
        }

        $token = Yii::$app->params['erse']['bearerToken'] ?? '';
        if ($token === '') {
            $this->markFailed($sync, ['error' => 'configuration', 'message' => 'Falta ERSE_BEARER_TOKEN en configuración.']);
            return [
                'httpStatus' => 500,
                'body' => ['error' => 'configuration', 'message' => 'Token ERSE no configurado.'],
            ];
        }

        $baseUrl = Yii::$app->params['erse']['baseUrl'] ?? 'https://api.erse.pt/v2';

        $http = new Client([
            'baseUrl' => rtrim($baseUrl, '/'),
            'requestConfig' => [
                'options' => [
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_TIMEOUT => 20,
                ],
            ],
        ]);

        try {
            $response = $http->createRequest()
                ->setMethod('POST')
                ->setUrl('/contracts')
                ->setFormat(Client::FORMAT_JSON)
                ->setData($payload)
                ->addHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ])
                ->send();
        } catch (\Throwable $e) {
            $this->markFailed($sync, ['exception' => $e->getMessage()]);
            return [
                'httpStatus' => 502,
                'body' => [
                    'error' => 'upstream_unreachable',
                    'message' => 'No se pudo contactar con ERSE.',
                ],
            ];
        }

        $status = $response->statusCode;
        $raw = $response->content;
        $parsed = $this->tryDecodeJson($raw);

        $sync->erse_response = is_array($parsed) ? Json::encode($parsed) : (string) $raw;
        $sync->updated_at = date('Y-m-d H:i:s');

        if ($status === 201) {
            $sync->sync_status = ErseContractSync::STATUS_SUCCESS;
            $sync->erse_id = $parsed['erse_id'] ?? null;
            $sync->save(false);
            return [
                'httpStatus' => 201,
                'body' => [
                    'erse_id' => $sync->erse_id,
                    'status' => $parsed['status'] ?? 'registered',
                    'sync_id' => $sync->id,
                ],
            ];
        }

        if ($status === 400) {
            $sync->sync_status = ErseContractSync::STATUS_FAILED;
            $sync->save(false);
            return [
                'httpStatus' => 400,
                'body' => $parsed ?? ['error' => 'validation_error', 'details' => $raw],
            ];
        }

        if ($status === 409) {
            $sync->sync_status = ErseContractSync::STATUS_FAILED;
            $existing = $parsed['existing_id'] ?? null;
            $sync->erse_id = $existing;
            $sync->save(false);
            return [
                'httpStatus' => 409,
                'body' => $parsed ?? ['error' => 'duplicate_contract'],
            ];
        }

        if ($status >= 500) {
            $sync->sync_status = ErseContractSync::STATUS_FAILED;
            $sync->save(false);
            return [
                'httpStatus' => 502,
                'body' => ['error' => 'erse_server_error', 'message' => 'ERSE respondió con error 5xx.'],
            ];
        }

        $sync->sync_status = ErseContractSync::STATUS_FAILED;
        $sync->save(false);
        return [
            'httpStatus' => 502,
            'body' => ['error' => 'unexpected_response', 'status' => $status, 'body' => $parsed ?? $raw],
        ];
    }

    private function buildPayload(Contract $contract): array
    {
        $client = $contract->client;
        $erse = Yii::$app->params['erse'];
        $street = $client->street ?: ($erse['defaultAddress']['street'] ?? 'Calle sin indicar 1');
        $city = $client->city ?: ($erse['defaultAddress']['city'] ?? 'Lisboa');
        $postal = $client->postal_code ?: ($erse['defaultAddress']['postal_code'] ?? '1000-001');

        return [
            'nif' => $client->fiscal_id,
            'cups' => $contract->cups,
            'supply_address' => [
                'street' => $street,
                'city' => $city,
                'postal_code' => $postal,
            ],
            'tariff_code' => $erse['tariffCodeDefault'] ?? 'ERSE_REGULATED_01',
            'start_date' => substr((string) $contract->start_date, 0, 10),
            'estimated_annual_kwh' => (int) ($erse['defaultEstimatedAnnualKwh'] ?? 3500),
        ];
    }

    private function markFailed(ErseContractSync $sync, array $info): void
    {
        $sync->sync_status = ErseContractSync::STATUS_FAILED;
        $sync->erse_response = Json::encode($info);
        $sync->updated_at = date('Y-m-d H:i:s');
        $sync->save(false);
    }

    private function tryDecodeJson(string $raw): ?array
    {
        try {
            $data = Json::decode($raw);
            return is_array($data) ? $data : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
