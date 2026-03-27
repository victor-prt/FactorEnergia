<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $contract_id
 * @property string $reading_date
 * @property float $kwh_consumed
 */
class MeterReading extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%meter_readings}}';
    }
}
