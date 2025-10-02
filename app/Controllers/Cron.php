<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\ReportScheduleModel;

/**
 * Controller para processar agendamentos via HTTP
 * Útil quando não há acesso a cron jobs no servidor
 * 
 * SEGURANÇA: Use token secreto para evitar execuções não autorizadas
 */
class Cron extends Controller
{
    /**
     * Processa agendamentos de relatórios
     * 
     * Acesso: https://seu-site.com/cron/process-reports?token=SEU_TOKEN_SECRETO
     * 
     * Configure em serviços como:
     * - https://www.easycron.com
     * - https://cron-job.org
     * - https://www.setcronjob.com
     */
    public function processReports()
    {
        // ==================== SEGURANÇA ==================== //
        
        // Token secreto (configure em .env)
        $tokenEsperado = env('CRON_TOKEN', 'CHANGE_ME_IN_PRODUCTION');
        $tokenRecebido = $this->request->getGet('token');
        
        if ($tokenRecebido !== $tokenEsperado) {
            log_message('warning', 'Tentativa não autorizada de executar cron via HTTP');
            return $this->response->setStatusCode(403)->setJSON([
                'error' => 'Acesso negado'
            ]);
        }
        
        // ================================================== //
        
        set_time_limit(300); // 5 minutos
        
        $inicio = microtime(true);
        $processados = 0;
        $erros = 0;
        
        try {
            $model = new ReportScheduleModel();
            $agendamentos = $model
                ->where('is_active', 1)
                ->where('next_run <=', date('Y-m-d H:i:s'))
                ->findAll();
            
            if (empty($agendamentos)) {
                log_message('info', '[CRON HTTP] Nenhum agendamento pendente');
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Nenhum agendamento pendente',
                    'processados' => 0,
                    'tempo' => number_format(microtime(true) - $inicio, 2) . 's'
                ]);
            }
            
            log_message('info', '[CRON HTTP] Encontrados ' . count($agendamentos) . ' agendamento(s)');
            
            foreach ($agendamentos as $ag) {
                try {
                    // Processar agendamento (você pode importar a lógica do Command aqui)
                    $this->processarAgendamento($ag, $model);
                    $processados++;
                    
                    log_message('info', "[CRON HTTP] ✓ Empresa #{$ag['id_empresa']} - Agendamento #{$ag['id_schedule']}");
                    
                } catch (\Exception $e) {
                    $erros++;
                    log_message('error', "[CRON HTTP] ✗ Erro agendamento #{$ag['id_schedule']}: " . $e->getMessage());
                }
            }
            
            $tempo = microtime(true) - $inicio;
            
            log_message('info', "[CRON HTTP] Finalizado - Processados: {$processados}, Erros: {$erros}, Tempo: " . number_format($tempo, 2) . "s");
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Processamento concluído',
                'total' => count($agendamentos),
                'processados' => $processados,
                'erros' => $erros,
                'tempo' => number_format($tempo, 2) . 's'
            ]);
            
        } catch (\Exception $e) {
            log_message('error', '[CRON HTTP] Erro geral: ' . $e->getMessage());
            
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error' => 'Erro ao processar agendamentos',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Processa um agendamento individual
     */
    private function processarAgendamento($agendamento, $model)
    {
        // TODO: Implementar lógica de geração de relatório e envio de email
        // Por enquanto, apenas atualiza o next_run
        
        $proximoEnvio = $this->calcularProximoEnvio(
            $agendamento['frequency'],
            $agendamento['schedule_time']
        );
        
        $model->update($agendamento['id_schedule'], [
            'last_sent_at' => date('Y-m-d H:i:s'),
            'next_run' => $proximoEnvio
        ]);
    }
    
    /**
     * Calcula próximo envio
     */
    private function calcularProximoEnvio($frequency, $time)
    {
        switch ($frequency) {
            case 'daily':
                return date('Y-m-d', strtotime('+1 day')) . ' ' . $time;
                
            case 'weekly':
                return date('Y-m-d', strtotime('+7 days')) . ' ' . $time;
                
            case 'monthly':
                return date('Y-m-d', strtotime('+1 month')) . ' ' . $time;
                
            default:
                return date('Y-m-d H:i:s', strtotime('+1 day'));
        }
    }
    
    /**
     * Status dos agendamentos (para monitoramento)
     * 
     * Acesso: https://seu-site.com/cron/status?token=SEU_TOKEN
     */
    public function status()
    {
        $token = $this->request->getGet('token');
        $tokenEsperado = env('CRON_TOKEN', 'CHANGE_ME_IN_PRODUCTION');
        
        if ($token !== $tokenEsperado) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Acesso negado']);
        }
        
        $model = new ReportScheduleModel();
        
        $stats = [
            'total' => $model->countAll(),
            'ativos' => $model->where('is_active', 1)->countAllResults(false),
            'inativos' => $model->where('is_active', 0)->countAllResults(false),
            'pendentes' => $model
                ->where('is_active', 1)
                ->where('next_run <=', date('Y-m-d H:i:s'))
                ->countAllResults(false),
            'proximos' => $model
                ->where('is_active', 1)
                ->where('next_run >', date('Y-m-d H:i:s'))
                ->orderBy('next_run', 'ASC')
                ->limit(5)
                ->findAll()
        ];
        
        return $this->response->setJSON($stats);
    }
}
