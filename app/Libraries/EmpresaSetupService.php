<?php

namespace App\Libraries;

use App\Models\ReportScheduleModel;
use App\Models\DashboardConfigModel;

/**
 * Serviço para configuração inicial de novas empresas
 * Cria agendamentos padrão, configurações de dashboard, etc
 */
class EmpresaSetupService
{
    protected $reportScheduleModel;
    protected $dashboardConfigModel;

    public function __construct()
    {
        $this->reportScheduleModel = new ReportScheduleModel();
        $this->dashboardConfigModel = new DashboardConfigModel();
    }

    /**
     * Configura tudo que uma nova empresa precisa
     * 
     * @param int $idEmpresa
     * @param int $idContador
     * @param string $emailEmpresa
     * @param int $idLogin (opcional - do usuário master da empresa)
     * @return bool
     */
    public function setupNovaEmpresa($idEmpresa, $idContador, $emailEmpresa, $idLogin = null)
    {
        try {
            // 1. Criar agendamentos padrão
            $this->criarAgendamentosPadrao($idEmpresa, $idContador, $emailEmpresa);

            // 2. Criar configuração de dashboard padrão
            if ($idLogin) {
                $this->criarDashboardPadrao($idEmpresa, $idLogin);
            }

            log_message('info', "Setup completo para empresa #{$idEmpresa}");
            return true;

        } catch (\Exception $e) {
            log_message('error', "Erro no setup da empresa #{$idEmpresa}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cria agendamentos padrão para nova empresa
     */
    private function criarAgendamentosPadrao($idEmpresa, $idContador, $emailEmpresa)
    {
        $agendamentos = [
            // Relatório de vendas diário às 8h
            [
                'id_empresa' => $idEmpresa,
                'id_contador' => $idContador,
                'report_type' => 'vendas',
                'frequency' => 'daily',
                'format' => 'excel',
                'email_recipients' => $emailEmpresa,
                'schedule_time' => '08:00:00',
                'next_run' => $this->calcularProximoEnvio('daily', '08:00:00'),
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            // Alerta de estoque semanal às 9h (toda segunda)
            [
                'id_empresa' => $idEmpresa,
                'id_contador' => $idContador,
                'report_type' => 'estoque',
                'frequency' => 'weekly',
                'format' => 'excel',
                'email_recipients' => $emailEmpresa,
                'schedule_time' => '09:00:00',
                'next_run' => $this->calcularProximoEnvio('weekly', '09:00:00'),
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            // Relatório fiscal mensal (inativo por padrão)
            [
                'id_empresa' => $idEmpresa,
                'id_contador' => $idContador,
                'report_type' => 'fiscal',
                'frequency' => 'monthly',
                'format' => 'pdf',
                'email_recipients' => $emailEmpresa,
                'schedule_time' => '10:00:00',
                'next_run' => $this->calcularProximoEnvio('monthly', '10:00:00'),
                'is_active' => 0, // Inativo - empresa ativa se quiser
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        $this->reportScheduleModel->insertBatch($agendamentos);
        log_message('info', "Agendamentos padrão criados para empresa #{$idEmpresa}");
    }

    /**
     * Cria configuração de dashboard padrão
     */
    private function criarDashboardPadrao($idEmpresa, $idLogin)
    {
        $config = [
            'id_empresa' => $idEmpresa,
            'id_login' => $idLogin,
            'widgets' => json_encode(['vendas', 'ticket', 'produtos', 'pagamentos', 'grafico']),
            'layout' => 'default',
            'theme' => 'default',
            'default_period' => 'month',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $this->dashboardConfigModel->insert($config);
        log_message('info', "Dashboard padrão criado para empresa #{$idEmpresa}, login #{$idLogin}");
    }

    /**
     * Calcula próximo envio baseado na frequência
     */
    private function calcularProximoEnvio($frequency, $time)
    {
        switch ($frequency) {
            case 'daily':
                $next = date('Y-m-d') . ' ' . $time;
                if (strtotime($next) < time()) {
                    $next = date('Y-m-d', strtotime('+1 day')) . ' ' . $time;
                }
                return $next;
                
            case 'weekly':
                return date('Y-m-d', strtotime('next Monday')) . ' ' . $time;
                
            case 'monthly':
                return date('Y-m-01', strtotime('first day of next month')) . ' ' . $time;
                
            default:
                return date('Y-m-d H:i:s', strtotime('+1 day'));
        }
    }

    /**
     * Desativa todos os agendamentos de uma empresa
     * Útil quando empresa é suspensa/cancelada
     */
    public function desativarAgendamentos($idEmpresa)
    {
        $this->reportScheduleModel
            ->where('id_empresa', $idEmpresa)
            ->set(['is_active' => 0])
            ->update();

        log_message('info', "Agendamentos desativados para empresa #{$idEmpresa}");
    }

    /**
     * Reativa agendamentos de uma empresa
     */
    public function reativarAgendamentos($idEmpresa)
    {
        $this->reportScheduleModel
            ->where('id_empresa', $idEmpresa)
            ->set(['is_active' => 1])
            ->update();

        log_message('info', "Agendamentos reativados para empresa #{$idEmpresa}");
    }
}
