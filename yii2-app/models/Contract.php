<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $client_id
 * @property int $tariff_id
 * @property string $cups
 * @property string $start_date
 * @property string|null $end_date
 * @property string $status
 */
class Contract extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%contracts}}';
    }

    public function rules(): array
    {
        return [
            [['client_id', 'tariff_id', 'cups', 'start_date', 'status'], 'required'],
            [['client_id', 'tariff_id'], 'integer'],
            [['start_date', 'end_date'], 'safe'],
            [['cups'], 'string', 'max' => 25],
            [['status'], 'in', 'range' => ['active', 'cancelled', 'pending']],
        ];
    }

    public function getClient(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Client::class, ['id' => 'client_id']);
    }

    public function getTariff(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Tariff::class, ['id' => 'tariff_id']);
    }

    public function getErseSyncs(): \yii\db\ActiveQuery
    {
        return $this->hasMany(ErseContractSync::class, ['contract_id' => 'id']);
    }
}
