<?php

namespace app\services;

use app\components\SpotPriceClientInterface;
use app\models\Contract;
use app\models\Invoice;
use app\models\MeterReading;
use app\models\Tariff;
use Yii;
use yii\base\Component;
use yii\db\Connection;
use yii\db\Transaction;
use yii\di\Instance;

/**
 * Refactor de InvoiceCalculator (Parte 2.2): consultas parametrizadas, sin echo,
 * estrategias de tarifa, cliente de spot inyectable como componente Yii.
 */
class InvoiceCalculatorService extends Component
{
    /** @var Connection|array|string */
    public $db = 'db';

    /** @var SpotPriceClientInterface|array|string */
    public $spotPriceClient = 'spotPriceClient';

    private TariffStrategyResolver $tariffStrategyResolver;

    public function init(): void
    {
        parent::init();
        if ($this->db === 'db') {
            $this->db = Yii::$app->db;
        } else {
            $this->db = Instance::ensure($this->db, Connection::class);
        }

        if ($this->spotPriceClient === 'spotPriceClient') {
            $this->spotPriceClient = Yii::$app->spotPriceClient;
        } else {
            $this->spotPriceClient = Instance::ensure($this->spotPriceClient, SpotPriceClientInterface::class);
        }

        $this->tariffStrategyResolver = new TariffStrategyResolver();
    }

    /**
     * Calcula y persiste factura en borrador.
     *
     * @throws \InvalidArgumentException contrato/tarifa desconocidos o periodo inválido
     * @throws \RuntimeException duplicado o error de persistencia
     */
    public function calculateAndCreateDraft(int $contractId, string $monthYyyyMm): float
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $monthYyyyMm)) {
            throw new \InvalidArgumentException('Periodo debe ser YYYY-MM.');
        }

        $contract = Contract::find()
            ->with(['tariff', 'client'])
            ->where(['contracts.id' => $contractId])
            ->one();

        if ($contract === null) {
            throw new \InvalidArgumentException('Contrato no encontrado.');
        }

        /** @var Tariff|null $tariff */
        $tariff = $contract->tariff;
        if ($tariff === null) {
            throw new \RuntimeException('Tarifa no encontrada para el contrato.');
        }

        if (Invoice::find()->where(['contract_id' => $contractId, 'billing_period' => $monthYyyyMm])->exists()) {
            throw new \RuntimeException('Ya existe factura para este contrato y periodo.');
        }

        $totalKwh = $this->sumKwhForMonth($contractId, $monthYyyyMm);

        $strategy = $this->tariffStrategyResolver->resolve($tariff->code);
        $preTax = $strategy->calculatePreTaxAmount(
            $totalKwh,
            $contract,
            $tariff,
            $this->spotPriceClient,
            $monthYyyyMm,
        );

        $country = $contract->client->country ?? 'ES';
        $taxRate = $country === 'PT' ? 0.23 : 0.21;
        $total = $preTax + ($preTax * $taxRate);

        try {
            $tx = $this->db->beginTransaction(Transaction::READ_COMMITTED);
        } catch (\Throwable) {
            // Fallback para drivers que no soportan READ_COMMITTED (p. ej. SQLite en tests).
            $tx = $this->db->beginTransaction();
        }
        try {
            $invoice = new Invoice();
            $invoice->contract_id = $contractId;
            $invoice->billing_period = $monthYyyyMm;
            $invoice->total_kwh = $totalKwh;
            $invoice->total_amount = round($total, 2);
            $invoice->status = 'draft';
            if (!$invoice->insert()) {
                throw new \RuntimeException('No se pudo insertar la factura: ' . json_encode($invoice->errors));
            }
            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }

        return round($total, 2);
    }

    private function sumKwhForMonth(int $contractId, string $monthYyyyMm): float
    {
        $firstDay = $monthYyyyMm . '-01';
        $lastDay = date('Y-m-t', strtotime($firstDay));

        $sum = MeterReading::find()
            ->where(['contract_id' => $contractId])
            ->andWhere(['between', 'reading_date', $firstDay, $lastDay])
            ->sum('kwh_consumed');

        return $sum !== null ? (float) $sum : 0.0;
    }
}
