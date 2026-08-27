<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AgendamentosPadraoSeeder extends Seeder
{
    /**
     * Cria agendamentos padrão para uma empresa
     * 
     * @param int $idEmpresa
     * @param int $idContador
     * @param string $emailEmpresa
     */
    public function createDefaultSchedules($idEmpresa, $idContador, $emailEmpresa)
    {
        $agendamentos = [
            [
                'id_empresa' => $idEmpresa,
                'id_contador' => $idContador,
                'report_type' => 'vendas',
                'frequency' => 'daily',
                'format' => 'excel',
                'email_recipients' => $emailEmpresa,
                'schedule_time' => '08:00:00',
                'next_run' => date('Y-m-d', strtotime('+1 day')) . ' 08:00:00',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'id_empresa' => $idEmpresa,
                'id_contador' => $idContador,
                'report_type' => 'estoque',
                'frequency' => 'weekly',
                'format' => 'excel',
                'email_recipients' => $emailEmpresa,
                'schedule_time' => '09:00:00',
                'next_run' => date('Y-m-d', strtotime('next Monday')) . ' 09:00:00',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'id_empresa' => $idEmpresa,
                'id_contador' => $idContador,
                'report_type' => 'fiscal',
                'frequency' => 'monthly',
                'format' => 'pdf',
                'email_recipients' => $emailEmpresa,
                'schedule_time' => '10:00:00',
                'next_run' => date('Y-m-01', strtotime('first day of next month')) . ' 10:00:00',
                'is_active' => 0, // Inativo por padrão
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        $this->db->table('report_schedules')->insertBatch($agendamentos);
    }

    public function run()
    {
        // Este método pode ser usado para criar agendamentos para empresas existentes
        // Exemplo de uso:
        // $this->createDefaultSchedules(1, 1, 'empresa@exemplo.com');
    }
}
