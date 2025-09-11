<?php namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCurrentPeriodEndToEmpresas extends Migration
{
    public function up()
    {
        $fields = [
            'current_period_end' => [
                'type' => 'DATETIME',
                'null' => TRUE,
            ],
        ];

        $this->forge->addColumn('empresas', $fields);
        try {
            $this->db->query("CREATE INDEX IF NOT EXISTS idx_empresas_current_period_end ON `empresas` (`current_period_end`)");
        } catch (\Throwable $e) {}
    }

    public function down()
    {
        $this->forge->dropColumn('empresas', 'current_period_end');
    }
}


