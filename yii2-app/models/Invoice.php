<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $contract_id
 * @property string $billing_period
 * @property float|null $total_kwh
 * @property float|null $total_amount
 * @property string $status
 */
class Invoice extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%invoices}}';
    }
}
