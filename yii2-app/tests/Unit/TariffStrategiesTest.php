<?php

declare(strict_types=1);

namespace tests\Unit;

use app\models\Contract;
use app\models\Tariff;
use app\services\tariff\FixTariffStrategy;
use app\services\tariff\FlatRateTariffStrategy;
use app\services\tariff\IndexTariffStrategy;
use PHPUnit\Framework\TestCase;
use tests\Support\FixedSpotPriceClient;

final class TariffStrategiesTest extends TestCase
{
    public function testFixTariffStrategyCalculatesWithPromoDiscount(): void
    {
        $strategy = new FixTariffStrategy();
        $tariff = new Tariff(['code' => 'FIX_PROMO', 'price_per_kwh' => 0.20, 'fixed_monthly' => 10.00]);

        $amount = $strategy->calculatePreTaxAmount(
            100.0,
            new Contract(),
            $tariff,
            new FixedSpotPriceClient(0.0),
            '2026-03'
        );

        self::assertTrue($strategy->supports('FIX_PROMO'));
        self::assertSame(27.0, round($amount, 2)); // (100*0.20 + 10) * 0.9
    }

    public function testIndexTariffStrategyCalculatesAndAppliesDiscountOver500Kwh(): void
    {
        $strategy = new IndexTariffStrategy();
        $tariff = new Tariff(['code' => 'INDEX_PT', 'fixed_monthly' => 5.00]);

        $amount = $strategy->calculatePreTaxAmount(
            600.0,
            new Contract(),
            $tariff,
            new FixedSpotPriceClient(0.15),
            '2026-03'
        );

        self::assertTrue($strategy->supports('INDEX_PT'));
        self::assertSame(90.25, round($amount, 2)); // (600*0.15+5)*0.95
    }

    public function testFlatRateStrategyReturnsOnlyFixedMonthly(): void
    {
        $strategy = new FlatRateTariffStrategy();
        $tariff = new Tariff(['code' => 'FLAT_RATE', 'fixed_monthly' => 18.75]);

        $amount = $strategy->calculatePreTaxAmount(
            999.0,
            new Contract(),
            $tariff,
            new FixedSpotPriceClient(999.0),
            '2026-03'
        );

        self::assertTrue($strategy->supports('FLAT_RATE'));
        self::assertSame(18.75, round($amount, 2));
    }
}
