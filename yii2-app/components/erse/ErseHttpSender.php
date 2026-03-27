<?php

namespace app\components\erse;

use yii\base\Component;
use yii\helpers\Json;
use yii\httpclient\Client;

class ErseHttpSender extends Component implements ContractSyncSenderInterface
{
    public string $baseUrl = 'https://api.erse.pt/v2';
    public string $bearerToken = '';
    public int $connectTimeout = 5;
    public int $timeout = 20;

    public function send(string $destination, array $payload): array
    {
        if ($this->bearerToken === '') {
            throw new \RuntimeException('Token ERSE no configurado.');
        }

        $http = new Client([
            'baseUrl' => rtrim($this->baseUrl, '/'),
            'requestConfig' => [
                'options' => [
                    CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
                    CURLOPT_TIMEOUT => $this->timeout,
                ],
            ],
        ]);

        $response = $http->createRequest()
            ->setMethod('POST')
            ->setUrl($destination)
            ->setFormat(Client::FORMAT_JSON)
            ->setData($payload)
            ->addHeaders([
                'Authorization' => 'Bearer ' . $this->bearerToken,
                'Accept' => 'application/json',
            ])
            ->send();

        $raw = (string) $response->content;
        $parsed = null;
        try {
            $data = Json::decode($raw);
            $parsed = is_array($data) ? $data : null;
        } catch (\Throwable) {
            $parsed = null;
        }

        return [
            'statusCode' => (int) $response->statusCode,
            'rawBody' => $raw,
            'parsedBody' => $parsed,
        ];
    }
}
