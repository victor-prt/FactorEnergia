<?php

namespace app\services\tariff;

use app\components\SpotPriceClientInterface;
use app\models\Contract;
use app\models\Tariff;

interface TariffPricingStrategyInterface
{
    public function supports(string $tariffCode): bool;

    /**
     * Importe antes de impuestos (sin IVA).
     */
    public function calculatePreTaxAmount(
        float $totalKwh,
        Contract $contract,
        Tariff $tariff,
        SpotPriceClientInterface $spotPriceClient,
        string $billingMonth,
    ): float;
}
