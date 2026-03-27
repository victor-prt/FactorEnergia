<?php

declare(strict_types=1);

namespace tests\Support;

use app\components\SpotPriceClientInterface;

class ThrowingSpotPriceClient implements SpotPriceClientInterface
{
    public function fetchAveragePriceForMonth(string $month): float
    {
        throw new \RuntimeException('Fallo simulado de cliente spot.');
    }
}
