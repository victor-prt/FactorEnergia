<?php

namespace app\services\tariff;

use app\components\SpotPriceClientInterface;
use app\models\Contract;
use app\models\Tariff;

class FixTariffStrategy implements TariffPricingStrategyInterface
{
    public function supports(string $tariffCode): bool
    {
        return str_contains($tariffCode, 'FIX');
    }

    public function calculatePreTaxAmount(
        float $totalKwh,
        Contract $contract,
        Tariff $tariff,
        SpotPriceClientInterface $spotPriceClient,
        string $billingMonth,
    ): float {
        $amount = $totalKwh * (float) $tariff->price_per_kwh + (float) $tariff->fixed_monthly;
        if ($tariff->code === 'FIX_PROMO') {
            $amount *= 0.9;
        }
        return $amount;
    }
}
