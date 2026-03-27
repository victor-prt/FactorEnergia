<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $code
 * @property string|null $description
 * @property float $price_per_kwh
 * @property float $fixed_monthly
 * @property string $country
 */
class Tariff extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%tariffs}}';
    }
}
