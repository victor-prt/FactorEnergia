<?php

declare(strict_types=1);

namespace tests\Unit;

use app\models\Client;
use app\models\Contract;
use app\models\Invoice;
use app\models\MeterReading;
use app\models\Tariff;
use app\services\InvoiceCalculatorService;
use PHPUnit\Framework\TestCase;
use tests\Support\FixedSpotPriceClient;
use yii\console\Application;
use yii\db\Connection;
use yii\db\Schema;

final class InvoiceCalculatorServiceTest extends TestCase
{
    private static Connection $db;

    public static function setUpBeforeClass(): void
    {
        if (\Yii::$app === null) {
            new Application([
                'id' => 'tests',
                'basePath' => dirname(__DIR__, 2),
                'components' => [
                    'db' => [
                        'class' => Connection::class,
                        'dsn' => 'sqlite::memory:',
                    ],
                ],
            ]);
        }

        self::$db = \Yii::$app->db;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->rebuildSchema();
        $this->seedMinimalData();
    }

    public function testRejectsInvalidPeriodFormat(): void
    {
        $service = new InvoiceCalculatorService([
            'db' => self::$db,
            'spotPriceClient' => new FixedSpotPriceClient(0.15),
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $service->calculateAndCreateDraft(1, '202603');
    }

    public function testRejectsDuplicateInvoiceForSamePeriod(): void
    {
        self::$db->createCommand()->insert('invoices', [
            'contract_id' => 1,
            'billing_period' => '2026-03',
            'total_kwh' => 100.0,
            'total_amount' => 20.0,
            'status' => 'draft',
        ])->execute();

        $service = new InvoiceCalculatorService([
            'db' => self::$db,
            'spotPriceClient' => new FixedSpotPriceClient(0.15),
        ]);

        $this->expectException(\RuntimeException::class);
        $service->calculateAndCreateDraft(1, '2026-03');
    }

    public function testCreatesInvoiceAndReturnsAmount(): void
    {
        $service = new InvoiceCalculatorService([
            'db' => self::$db,
            'spotPriceClient' => new FixedSpotPriceClient(0.15),
        ]);

        $total = $service->calculateAndCreateDraft(1, '2026-03');

        self::assertSame(1, (int) Invoice::find()->count());
        // FIX_PROMO: (100*0.20 + 10)=30 -> *0.9=27; ES IVA 21% => 32.67
        self::assertSame(32.67, $total);
    }

    private function rebuildSchema(): void
    {
        self::$db->createCommand('DROP TABLE IF EXISTS invoices')->execute();
        self::$db->createCommand('DROP TABLE IF EXISTS meter_readings')->execute();
        self::$db->createCommand('DROP TABLE IF EXISTS contracts')->execute();
        self::$db->createCommand('DROP TABLE IF EXISTS tariffs')->execute();
        self::$db->createCommand('DROP TABLE IF EXISTS clients')->execute();

        self::$db->createCommand()->createTable('clients', [
            'id' => Schema::TYPE_PK,
            'fiscal_id' => Schema::TYPE_STRING . '(20) NOT NULL',
            'full_name' => Schema::TYPE_STRING . '(200) NOT NULL',
            'country' => Schema::TYPE_STRING . '(2) NOT NULL',
        ])->execute();

        self::$db->createCommand()->createTable('tariffs', [
            'id' => Schema::TYPE_PK,
            'code' => Schema::TYPE_STRING . '(20) NOT NULL',
            'price_per_kwh' => Schema::TYPE_DECIMAL . '(10,6) NOT NULL',
            'fixed_monthly' => Schema::TYPE_DECIMAL . '(10,2) NOT NULL',
            'country' => Schema::TYPE_STRING . '(2) NOT NULL',
        ])->execute();

        self::$db->createCommand()->createTable('contracts', [
            'id' => Schema::TYPE_PK,
            'client_id' => Schema::TYPE_INTEGER . ' NOT NULL',
            'tariff_id' => Schema::TYPE_INTEGER . ' NOT NULL',
            'cups' => Schema::TYPE_STRING . '(25) NOT NULL',
            'start_date' => Schema::TYPE_DATE . ' NOT NULL',
            'status' => Schema::TYPE_STRING . '(20) NOT NULL',
        ])->execute();

        self::$db->createCommand()->createTable('meter_readings', [
            'id' => Schema::TYPE_PK,
            'contract_id' => Schema::TYPE_INTEGER . ' NOT NULL',
            'reading_date' => Schema::TYPE_DATE . ' NOT NULL',
            'kwh_consumed' => Schema::TYPE_DECIMAL . '(12,3) NOT NULL',
        ])->execute();

        self::$db->createCommand()->createTable('invoices', [
            'id' => Schema::TYPE_PK,
            'contract_id' => Schema::TYPE_INTEGER . ' NOT NULL',
            'billing_period' => Schema::TYPE_STRING . '(7) NOT NULL',
            'total_kwh' => Schema::TYPE_DECIMAL . '(12,3)',
            'total_amount' => Schema::TYPE_DECIMAL . '(10,2)',
            'status' => Schema::TYPE_STRING . '(20) NOT NULL',
        ])->execute();
    }

    private function seedMinimalData(): void
    {
        (new Client([
            'id' => 1,
            'fiscal_id' => 'ES-TEST-1',
            'full_name' => 'Cliente Test',
            'country' => 'ES',
        ]))->save(false);

        (new Tariff([
            'id' => 1,
            'code' => 'FIX_PROMO',
            'price_per_kwh' => 0.20,
            'fixed_monthly' => 10.00,
            'country' => 'ES',
        ]))->save(false);

        (new Contract([
            'id' => 1,
            'client_id' => 1,
            'tariff_id' => 1,
            'cups' => 'TESTCUPS001',
            'start_date' => '2026-01-01',
            'status' => 'active',
        ]))->save(false);

        (new MeterReading([
            'contract_id' => 1,
            'reading_date' => '2026-03-10',
            'kwh_consumed' => 100.0,
        ]))->save(false);
    }
}
