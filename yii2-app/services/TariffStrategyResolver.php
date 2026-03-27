<?php

namespace app\services;

use app\services\tariff\FixTariffStrategy;
use app\services\tariff\FlatRateTariffStrategy;
use app\services\tariff\IndexTariffStrategy;
use app\services\tariff\TariffPricingStrategyInterface;

/**
 * Resuelve la estrategia de tarifa. Para añadir un tipo nuevo: crear una clase
 * que implemente TariffPricingStrategyInterface y registrarla en $strategies.
 */
class TariffStrategyResolver
{
    /** @var TariffPricingStrategyInterface[] */
    private array $strategies;

    public function __construct()
    {
        $this->strategies = [
            new FixTariffStrategy(),
            new IndexTariffStrategy(),
            new FlatRateTariffStrategy(),
        ];
    }

    public function resolve(string $tariffCode): TariffPricingStrategyInterface
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($tariffCode)) {
                return $strategy;
            }
        }
        throw new \InvalidArgumentException('Tipo de tarifa desconocido: ' . $tariffCode);
    }

    /**
     * @param TariffPricingStrategyInterface[] $extra
     */
    public function register(TariffPricingStrategyInterface $strategy): void
    {
        array_unshift($this->strategies, $strategy);
    }
}
