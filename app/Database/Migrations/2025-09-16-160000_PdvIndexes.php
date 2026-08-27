<?php namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class PdvIndexes extends Migration
{
    public function up()
    {
        // cash_registers indexes
        $this->forge->addKey('id_contador');
        $this->forge->addKey('id_empresa');
        $this->forge->addKey('status');
        $this->forge->processIndexes('cash_registers');

        // shifts indexes
        $this->forge->addKey('id_cash_register');
        $this->forge->addKey('id_contador');
        $this->forge->addKey('id_empresa');
        $this->forge->addKey('status');
        $this->forge->addKey('opened_at');
        $this->forge->processIndexes('shifts');

        // pos_sales indexes + unique sale_number por empresa
        $this->forge->addKey('id_shift');
        $this->forge->addKey('id_cash_register');
        $this->forge->addKey('id_contador');
        $this->forge->addKey('id_empresa');
        $this->forge->addKey('status');
        $this->forge->addKey('payment_type');
        $this->forge->processIndexes('pos_sales');

        // Unique composto: (id_empresa, sale_number)
        $db = \Config\Database::connect();
        $db->query('CREATE UNIQUE INDEX IF NOT EXISTS pos_sales_empresa_sale_unique ON pos_sales (id_empresa, sale_number)');
    }

    public function down()
    {
        $db = \Config\Database::connect();
        // Remover índice único se existir (MySQL não suporta IF EXISTS uniformemente; tente e ignore)
        try { $db->query('DROP INDEX pos_sales_empresa_sale_unique ON pos_sales'); } catch (\Throwable $e) {}
    }
}


