<?php

declare(strict_types=1);

namespace tests\Unit;

use PHPUnit\Framework\TestCase;
use tests\Support\FixedSpotPriceClient;
use tests\Support\ThrowingSpotPriceClient;

final class SpotPriceClientDoubleTest extends TestCase
{
    public function testFixedSpotClientReturnsConfiguredValue(): void
    {
        $client = new FixedSpotPriceClient(0.1234);
        self::assertSame(0.1234, $client->fetchAveragePriceForMonth('2026-03'));
    }

    public function testThrowingSpotClientRaisesRuntimeException(): void
    {
        $client = new ThrowingSpotPriceClient();

        $this->expectException(\RuntimeException::class);
        $client->fetchAveragePriceForMonth('2026-03');
    }
}
