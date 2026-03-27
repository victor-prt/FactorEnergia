<?php

namespace app\components\erse;

use yii\base\Component;

class ErseMockSender extends Component implements ContractSyncSenderInterface
{
    public function send(string $destination, array $payload): array
    {
        $erseId = 'ERSE-MOCK-' . substr(sha1((string) ($payload['cups'] ?? 'no-cups')), 0, 10);
        $mockBody = [
            'mock' => true,
            'erse_id' => $erseId,
            'status' => 'registered',
            'destination' => $destination,
            'requestPayload' => $payload,
        ];

        return [
            'statusCode' => 201,
            'rawBody' => json_encode($mockBody) ?: '{}',
            'parsedBody' => $mockBody,
        ];
    }
}
