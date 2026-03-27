<?php

namespace app\components;

/**
 * Abstrae la obtención del precio spot (testable / sustituible por mock).
 */
interface SpotPriceClientInterface
{
    /**
     * @return float Precio medio (avg_price) para el mes YYYY-MM
     * @throws \RuntimeException si la respuesta no es válida
     */
    public function fetchAveragePriceForMonth(string $month): float;
}
