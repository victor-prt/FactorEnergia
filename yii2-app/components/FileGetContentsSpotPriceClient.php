<?php

namespace app\components;

/**
 * Implementación de referencia usando HTTP vía file_get_contents (timeouts vía stream context en producción).
 */
class FileGetContentsSpotPriceClient implements SpotPriceClientInterface
{
    public function __construct(
        private readonly string $baseUrl = 'https://api.energy-market.eu/spot',
    ) {
    }

    public function fetchAveragePriceForMonth(string $month): float
    {
        $url = $this->baseUrl . '?month=' . rawurlencode($month);
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 10,
                'ignore_errors' => true,
            ],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            throw new \RuntimeException('No se pudo contactar con el servicio de precio spot.');
        }
        $data = json_decode($raw, true);
        if (!is_array($data) || !isset($data['avg_price']) || !is_numeric($data['avg_price'])) {
            throw new \RuntimeException('Respuesta de precio spot inválida.');
        }
        return (float) $data['avg_price'];
    }
}
