<?php

namespace app\services\tariff;

use app\components\SpotPriceClientInterface;
use app\models\Contract;
use app\models\Tariff;

class IndexTariffStrategy implements TariffPricingStrategyInterface
{
    public function supports(string $tariffCode): bool
    {
        return str_contains($tariffCode, 'INDEX');
    }

    public function calculatePreTaxAmount(
        float $totalKwh,
        Contract $contract,
        Tariff $tariff,
        SpotPriceClientInterface $spotPriceClient,
        string $billingMonth,
    ): float {
        $avg = $spotPriceClient->fetchAveragePriceForMonth($billingMonth);
        $amount = $totalKwh * $avg + (float) $tariff->fixed_monthly;
        if ($totalKwh > 500) {
            $amount *= 0.95;
        }
        return $amount;
    }
}
