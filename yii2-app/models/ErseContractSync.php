<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Registro de intentos de sincronización con ERSE.
 *
 * @property int $id
 * @property int $contract_id
 * @property string|null $erse_id
 * @property string $sync_status pending|success|failed
 * @property string|null $erse_response
 * @property string $created_at
 * @property string $updated_at
 */
class ErseContractSync extends ActiveRecord
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';

    public static function tableName(): string
    {
        return '{{%erse_contract_sync}}';
    }

    public function rules(): array
    {
        return [
            [['contract_id', 'sync_status'], 'required'],
            [['contract_id'], 'integer'],
            [['erse_response'], 'string'],
            [['erse_id'], 'string', 'max' => 64],
            [['created_at', 'updated_at'], 'safe'],
            [['sync_status'], 'in', 'range' => [self::STATUS_PENDING, self::STATUS_SUCCESS, self::STATUS_FAILED]],
        ];
    }

    public function getContract(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Contract::class, ['id' => 'contract_id']);
    }
}
