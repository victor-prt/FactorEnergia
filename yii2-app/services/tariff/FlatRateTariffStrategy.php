<?php

namespace app\services\tariff;

use app\components\SpotPriceClientInterface;
use app\models\Contract;
use app\models\Tariff;

class FlatRateTariffStrategy implements TariffPricingStrategyInterface
{
    public function supports(string $tariffCode): bool
    {
        return $tariffCode === 'FLAT_RATE';
    }

    public function calculatePreTaxAmount(
        float $totalKwh,
        Contract $contract,
        Tariff $tariff,
        SpotPriceClientInterface $spotPriceClient,
        string $billingMonth,
    ): float {
        return (float) $tariff->fixed_monthly;
    }
}
