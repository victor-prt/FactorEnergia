<?php

use yii\db\Migration;

/**
 * Parte 3.1 — Tabla de sincronización ERSE y dirección opcional en clientes (mapeo a supply_address).
 */
class m260321_120000_erse_sync_and_client_address extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%clients}}', 'street', $this->string(200)->null());
        $this->addColumn('{{%clients}}', 'city', $this->string(120)->null());
        $this->addColumn('{{%clients}}', 'postal_code', $this->string(20)->null());

        $this->createTable('{{%erse_contract_sync}}', [
            'id' => $this->primaryKey(),
            'contract_id' => $this->integer()->notNull(),
            'erse_id' => $this->string(64)->null(),
            'sync_status' => $this->string(20)->notNull(),
            'erse_response' => $this->text()->null(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);

        $this->addForeignKey(
            'fk_erse_sync_contract',
            '{{%erse_contract_sync}}',
            'contract_id',
            '{{%contracts}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_erse_sync_contract', '{{%erse_contract_sync}}');
        $this->dropTable('{{%erse_contract_sync}}');
        $this->dropColumn('{{%clients}}', 'postal_code');
        $this->dropColumn('{{%clients}}', 'city');
        $this->dropColumn('{{%clients}}', 'street');
    }
}
