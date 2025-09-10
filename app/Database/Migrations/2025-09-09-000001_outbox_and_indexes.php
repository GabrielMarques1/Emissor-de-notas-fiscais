<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class OutboxAndIndexes extends Migration
{
    public function up()
    {
        // outbox_events
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'table_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 128,
            ],
            'primary_key_json' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'operation' => [
                'type'       => 'ENUM',
                'constraint' => ['insert', 'update', 'delete'],
            ],
            'payload' => [
                'type' => 'MEDIUMTEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'processed_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['table_name', 'created_at']);
        $this->forge->createTable('outbox_events', true);

        // Índices em updated_at onde aplicável
        $tables = ['clientes','empresas','produtos','configuracoes','contadores','fornecedores','transportadoras','municipios','ufs','pagamentos_da_empresa','nfes','nfces'];
        foreach ($tables as $table) {
            if ($this->db->tableExists($table)) {
                // cuidado: alguns esquemas podem não ter updated_at
                try {
                    $fields = array_map(function ($f) { return $f->name; }, $this->db->getFieldData($table));
                    if (in_array('updated_at', $fields, true)) {
                        $this->db->query("CREATE INDEX IF NOT EXISTS idx_{$table}_updated_at ON `{$table}` (`updated_at`)");
                    }
                } catch (\Throwable $e) {
                    // ignora se não suportado
                }
            }
        }
    }

    public function down()
    {
        $this->forge->dropTable('outbox_events', true);
        // índices podem permanecer, não é crítico removê-los
    }
}


