<?php

declare(strict_types=1);

namespace tests\Unit;

use app\components\SpotPriceClientInterface;
use app\models\Contract;
use app\models\Tariff;
use app\services\TariffStrategyResolver;
use app\services\tariff\FlatRateTariffStrategy;
use app\services\tariff\TariffPricingStrategyInterface;
use PHPUnit\Framework\TestCase;
use tests\Support\FixedSpotPriceClient;

final class TariffStrategyResolverTest extends TestCase
{
    public function testResolveKnownCodeReturnsExpectedStrategy(): void
    {
        $resolver = new TariffStrategyResolver();

        $strategy = $resolver->resolve('FLAT_RATE');

        self::assertInstanceOf(FlatRateTariffStrategy::class, $strategy);
    }

    public function testResolveUnknownCodeThrowsException(): void
    {
        $resolver = new TariffStrategyResolver();

        $this->expectException(\InvalidArgumentException::class);
        $resolver->resolve('UNKNOWN_TARIFF');
    }

    public function testRegisterAddsPriorityStrategy(): void
    {
        $resolver = new TariffStrategyResolver();
        $resolver->register(new class implements TariffPricingStrategyInterface {
            public function supports(string $tariffCode): bool
            {
                return $tariffCode === 'FIX_PROMO';
            }

            public function calculatePreTaxAmount(
                float $totalKwh,
                Contract $contract,
                Tariff $tariff,
                SpotPriceClientInterface $spotPriceClient,
                string $billingMonth
            ): float {
                return 123.45;
            }
        });

        $strategy = $resolver->resolve('FIX_PROMO');
        $amount = $strategy->calculatePreTaxAmount(
            1.0,
            new Contract(),
            new Tariff(),
            new FixedSpotPriceClient(0.0),
            '2026-03'
        );

        self::assertSame(123.45, $amount);
    }
}
