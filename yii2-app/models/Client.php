<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $fiscal_id
 * @property string $full_name
 * @property string|null $email
 * @property string $country
 * @property string|null $street
 * @property string|null $city
 * @property string|null $postal_code
 */
class Client extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%clients}}';
    }

    public function rules(): array
    {
        return [
            [['fiscal_id', 'full_name', 'country'], 'required'],
            [['fiscal_id', 'email', 'street', 'city', 'postal_code'], 'string', 'max' => 255],
            [['full_name'], 'string', 'max' => 200],
            [['country'], 'string', 'max' => 2],
        ];
    }

    public function getContracts(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Contract::class, ['client_id' => 'id']);
    }
}
