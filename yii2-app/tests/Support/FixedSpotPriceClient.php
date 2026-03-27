<?php

declare(strict_types=1);

namespace tests\Support;

use app\components\SpotPriceClientInterface;

class FixedSpotPriceClient implements SpotPriceClientInterface
{
    public function __construct(private readonly float $avgPrice)
    {
    }

    public function fetchAveragePriceForMonth(string $month): float
    {
        return $this->avgPrice;
    }
}
